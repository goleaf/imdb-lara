<?php

namespace App\Actions\Import;

use App\Models\ImdbTitleImport;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;
use Throwable;

class DownloadImdbTitlePayloadAction
{
    /**
     * @var array<string, array<string, mixed>|null>
     */
    private array $artifactPayloadCache = [];

    public function __construct(
        private readonly FetchImdbJsonAction $fetchImdbJsonAction,
        private readonly FetchImdbGraphqlAction $fetchImdbGraphqlAction,
        private readonly ResolveImdbApiUrlAction $resolveImdbApiUrlAction,
    ) {}

    /**
     * @return array{
     *     downloaded: bool,
     *     imdb_id: string,
     *     payload: array<string, mixed>,
     *     payload_hash: string,
     *     source_url: string,
     *     storage_path: string|null
     * }
     */
    public function handle(string $imdbId, ?string $storageDirectory, string $urlTemplate, bool $force = false): array
    {
        $storagePath = $this->bundleStoragePath($storageDirectory, $imdbId);
        $artifactDirectory = $this->artifactDirectory($storageDirectory, $imdbId);

        if ($storagePath !== null && ! $force && File::exists($storagePath)) {
            $existingPayload = $this->decodePayload(File::get($storagePath));

            if ($this->canReuseExistingBundle($existingPayload, $artifactDirectory)) {
                $result = [
                    'downloaded' => false,
                    'imdb_id' => $imdbId,
                    'payload' => $existingPayload,
                    'payload_hash' => $this->payloadHash($existingPayload),
                    'source_url' => $this->resolveSourceUrl($imdbId, $urlTemplate),
                    'storage_path' => $storagePath,
                ];

                $this->trackImportArtifact($result);

                return $result;
            }
        }

        $sourceUrl = $this->resolveSourceUrl($imdbId, $urlTemplate);
        $payload = $this->buildBundle($imdbId, $sourceUrl, $artifactDirectory);

        if ($storagePath !== null) {
            File::ensureDirectoryExists(dirname($storagePath));
            File::put($storagePath, json_encode(
                $payload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            ));
        }

        $result = [
            'downloaded' => true,
            'imdb_id' => $imdbId,
            'payload' => $payload,
            'payload_hash' => $this->payloadHash($payload),
            'source_url' => $sourceUrl,
            'storage_path' => $storagePath,
        ];

        $this->trackImportArtifact($result);

        return $result;
    }

    /**
     * @param  array{
     *     imdb_id: string,
     *     payload: array<string, mixed>,
     *     payload_hash: string,
     *     source_url: string,
     *     storage_path: string|null
     * }  $result
     */
    private function trackImportArtifact(array $result): void
    {
        if ($result['storage_path'] === null) {
            return;
        }

        $importModel = new ImdbTitleImport;
        $connection = $importModel->getConnectionName();

        if (! Schema::connection($connection)->hasTable($importModel->getTable())) {
            return;
        }

        ImdbTitleImport::query()->updateOrCreate(
            ['imdb_id' => $result['imdb_id']],
            [
                'source_url' => $result['source_url'],
                'storage_path' => $result['storage_path'],
                'payload_hash' => $result['payload_hash'],
                'payload' => $result['payload'],
                'downloaded_at' => now(),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBundle(string $imdbId, string $sourceUrl, ?string $artifactDirectory): array
    {
        $artifacts = [];
        $downloadedAt = now()->toIso8601String();

        $titlePayload = $this->downloadArtifact(
            $artifactDirectory,
            'title.json',
            $sourceUrl,
            $artifacts,
            'title',
        );

        if (! is_array($titlePayload)) {
            throw new RuntimeException(sprintf('IMDb API returned an unexpected payload for [%s].', $imdbId));
        }

        $graphqlArtifacts = $this->downloadGraphqlTitleArtifacts($imdbId, $artifactDirectory, $artifacts);

        $titleArtifacts = $this->downloadArtifactsConcurrently($artifactDirectory, $artifacts, [
            ...$this->titleArtifactRequests($imdbId, $titlePayload, array_keys($graphqlArtifacts)),
        ], $this->titleBatchConcurrency());
        $credits = $graphqlArtifacts['credits'] ?? ($titleArtifacts['credits'] ?? null);
        $releaseDates = $titleArtifacts['releaseDates'] ?? null;
        $akas = $titleArtifacts['akas'] ?? null;
        $seasons = $titleArtifacts['seasons'] ?? null;
        $episodes = $titleArtifacts['episodes'] ?? null;
        $images = $titleArtifacts['images'] ?? null;
        $videos = $titleArtifacts['videos'] ?? null;
        $awardNominations = $titleArtifacts['awardNominations'] ?? null;
        $parentsGuide = $titleArtifacts['parentsGuide'] ?? null;
        $certificates = $graphqlArtifacts['certificates'] ?? ($titleArtifacts['certificates'] ?? null);
        $companyCredits = $titleArtifacts['companyCredits'] ?? null;
        $boxOffice = $titleArtifacts['boxOffice'] ?? null;

        $manifest = [
            'schemaVersion' => 1,
            'imdbId' => $imdbId,
            'downloadedAt' => $downloadedAt,
            'sourceUrl' => $sourceUrl,
            'artifacts' => $artifacts,
        ];

        $this->writeJsonArtifact($artifactDirectory, 'manifest.json', $manifest);

        return [
            'schemaVersion' => 3,
            'imdbId' => $imdbId,
            'downloadedAt' => $downloadedAt,
            'sourceUrl' => $sourceUrl,
            'title' => $titlePayload,
            'credits' => $credits,
            'releaseDates' => $releaseDates,
            'akas' => $akas,
            'seasons' => $seasons,
            'episodes' => $episodes,
            'images' => $images,
            'videos' => $videos,
            'awardNominations' => $awardNominations,
            'parentsGuide' => $parentsGuide,
            'certificates' => $certificates,
            'companyCredits' => $companyCredits,
            'boxOffice' => $boxOffice,
            'names' => [],
            'interests' => [],
            'artifacts' => $artifacts,
        ];
    }

    /**
     * @param  array<string, mixed>  $titlePayload
     * @return list<array{
     *     artifact_key: string,
     *     items_key?: string,
     *     nullable?: bool,
     *     relative_path: string,
     *     url: string
     * }>
     */
    private function titleArtifactRequests(string $imdbId, array $titlePayload, array $excludedArtifactKeys = []): array
    {
        $requests = [
            [
                'artifact_key' => 'credits',
                'relative_path' => 'credits.json',
                'url' => $this->titleEndpoint('title.credits', $imdbId),
                'items_key' => 'credits',
                'nullable' => true,
            ],
            [
                'artifact_key' => 'releaseDates',
                'relative_path' => 'release-dates.json',
                'url' => $this->titleEndpoint('title.release_dates', $imdbId),
                'items_key' => 'releaseDates',
                'nullable' => true,
            ],
            [
                'artifact_key' => 'akas',
                'relative_path' => 'akas.json',
                'url' => $this->titleEndpoint('title.akas', $imdbId),
                'items_key' => 'akas',
                'nullable' => true,
            ],
            [
                'artifact_key' => 'images',
                'relative_path' => 'images.json',
                'url' => $this->titleEndpoint('title.images', $imdbId),
                'items_key' => 'images',
                'nullable' => true,
            ],
            [
                'artifact_key' => 'videos',
                'relative_path' => 'videos.json',
                'url' => $this->titleEndpoint('title.videos', $imdbId),
                'items_key' => 'videos',
                'nullable' => true,
            ],
            [
                'artifact_key' => 'awardNominations',
                'relative_path' => 'award-nominations.json',
                'url' => $this->titleEndpoint('title.award_nominations', $imdbId),
                'items_key' => 'awardNominations',
                'nullable' => true,
            ],
            [
                'artifact_key' => 'parentsGuide',
                'relative_path' => 'parents-guide.json',
                'url' => $this->titleEndpoint('title.parents_guide', $imdbId),
                'nullable' => true,
            ],
            [
                'artifact_key' => 'certificates',
                'relative_path' => 'certificates.json',
                'url' => $this->titleEndpoint('title.certificates', $imdbId),
                'items_key' => 'certificates',
                'nullable' => true,
            ],
            [
                'artifact_key' => 'companyCredits',
                'relative_path' => 'company-credits.json',
                'url' => $this->titleEndpoint('title.company_credits', $imdbId),
                'items_key' => 'companyCredits',
                'nullable' => true,
            ],
            [
                'artifact_key' => 'boxOffice',
                'relative_path' => 'box-office.json',
                'url' => $this->titleEndpoint('title.box_office', $imdbId),
                'nullable' => true,
            ],
        ];

        $requests = array_values(array_filter(
            $requests,
            fn (array $request): bool => ! in_array($request['artifact_key'], $excludedArtifactKeys, true),
        ));

        if (! $this->shouldDownloadSeriesArtifacts($titlePayload)) {
            return $requests;
        }

        return [
            ...array_slice($requests, 0, 3),
            [
                'artifact_key' => 'seasons',
                'relative_path' => 'seasons.json',
                'url' => $this->titleEndpoint('title.seasons', $imdbId),
                'items_key' => 'seasons',
                'nullable' => true,
            ],
            [
                'artifact_key' => 'episodes',
                'relative_path' => 'episodes.json',
                'url' => $this->titleEndpoint('title.episodes', $imdbId),
                'items_key' => 'episodes',
                'nullable' => true,
            ],
            ...array_slice($requests, 3),
        ];
    }

    /**
     * @param  array<string, mixed>  $artifacts
     * @return array<string, array<string, mixed>>
     */
    private function downloadGraphqlTitleArtifacts(string $imdbId, ?string $artifactDirectory, array &$artifacts): array
    {
        if (! $this->fetchImdbGraphqlAction->enabled()) {
            return [];
        }

        try {
            $payloads = $this->fetchImdbGraphqlAction->fetchTitleCore($imdbId);
        } catch (Throwable $exception) {
            logger()->warning(sprintf(
                'IMDb GraphQL title optimization failed for [%s]; falling back to REST title endpoints. %s',
                $imdbId,
                $exception->getMessage(),
            ));

            return [];
        }

        foreach ([
            'credits' => 'credits.json',
            'certificates' => 'certificates.json',
        ] as $artifactKey => $relativePath) {
            if (! array_key_exists($artifactKey, $payloads)) {
                continue;
            }

            $this->writeJsonArtifact($artifactDirectory, $relativePath, $payloads[$artifactKey]);
            $artifact = [
                'url' => (string) config('services.imdb.graphql.url', 'https://graph.imdbapi.dev/v1'),
                'has_payload' => true,
            ];

            if ($artifactDirectory !== null) {
                $artifact['path'] = $relativePath;
            }

            data_set($artifacts, $artifactKey, $artifact);
        }

        return $payloads;
    }

    private function titleEndpoint(string $endpointKey, string $imdbId): string
    {
        return $this->resolveImdbApiUrlAction->handle(
            $this->resolveImdbApiUrlAction->endpoint($endpointKey),
            ['titleId' => $imdbId],
        );
    }

    private function canReuseExistingBundle(array $payload, ?string $artifactDirectory): bool
    {
        if ($artifactDirectory === null) {
            return false;
        }

        return (int) data_get($payload, 'schemaVersion', 0) >= 3
            && File::exists($artifactDirectory.DIRECTORY_SEPARATOR.'manifest.json');
    }

    /**
     * @param  array<string, mixed>  $artifacts
     * @return array<string, mixed>|null
     */
    private function downloadArtifact(
        ?string $artifactDirectory,
        string $relativePath,
        string $url,
        array &$artifacts,
        string $artifactKey,
        ?string $itemsKey = null,
        bool $nullable = false,
    ): ?array {
        if (array_key_exists($url, $this->artifactPayloadCache)) {
            $payload = $this->artifactPayloadCache[$url];
        } else {
            $payload = $this->downloadEndpointPayload($url, $artifactKey, $itemsKey, $nullable);

            $this->artifactPayloadCache[$url] = $payload;
        }

        $this->writeJsonArtifact($artifactDirectory, $relativePath, $payload);
        $artifact = [
            'url' => $url,
            'has_payload' => $payload !== null,
        ];

        if ($artifactDirectory !== null) {
            $artifact['path'] = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
        }

        data_set($artifacts, $artifactKey, $artifact);

        return $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function downloadEndpointPayload(string $url, string $artifactKey, ?string $itemsKey, bool $nullable): ?array
    {
        try {
            if ($itemsKey !== null) {
                return $this->fetchImdbJsonAction->paginate($url, $itemsKey, $nullable, $nullable);
            }

            $payload = $this->fetchImdbJsonAction->get($url, nullable: $nullable);

            if (! is_array($payload) || ! $this->hasNextPageToken($payload)) {
                return $payload;
            }

            $detectedItemsKey = $this->detectPaginatedItemsKey($payload, $artifactKey);

            if ($detectedItemsKey === null) {
                return $payload;
            }

            return $this->fetchImdbJsonAction->paginate($url, $detectedItemsKey, $nullable, $nullable);
        } catch (Throwable $exception) {
            if (! $nullable) {
                throw $exception;
            }

            logger()->warning(sprintf(
                'IMDb optional title endpoint failed for [%s]; continuing without this artifact. %s',
                $url,
                $exception->getMessage(),
            ));

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $artifacts
     * @param  list<array{
     *     artifact_key: string,
     *     items_key?: string,
     *     nullable?: bool,
     *     relative_path: string,
     *     url: string
     * }>  $requests
     * @return array<string, array<string, mixed>|null>
     */
    private function downloadArtifactsConcurrently(
        ?string $artifactDirectory,
        array &$artifacts,
        array $requests,
        int $concurrency,
    ): array {
        if ($requests === []) {
            return [];
        }

        $concurrency = max(1, $concurrency);

        $payloads = [];
        $batchRequests = [];

        foreach ($requests as $request) {
            $url = $request['url'];

            if (array_key_exists($url, $this->artifactPayloadCache)) {
                $payloads[$request['artifact_key']] = $this->artifactPayloadCache[$url];

                continue;
            }

            $batchRequests[] = [
                'key' => $request['artifact_key'],
                'url' => $url,
                'nullable' => (bool) ($request['nullable'] ?? false),
            ];
        }

        $initialPayloads = $this->fetchImdbJsonAction->getConcurrent($batchRequests, $concurrency);

        foreach ($requests as $request) {
            $artifactKey = $request['artifact_key'];
            $url = $request['url'];

            if (! array_key_exists($artifactKey, $payloads)) {
                $payload = $this->normalizeConcurrentPayload(
                    $url,
                    $artifactKey,
                    $initialPayloads[$artifactKey] ?? null,
                    $request['items_key'] ?? null,
                    (bool) ($request['nullable'] ?? false),
                );

                $this->artifactPayloadCache[$url] = $payload;
                $payloads[$artifactKey] = $payload;
            }

            $this->writeJsonArtifact($artifactDirectory, $request['relative_path'], $payloads[$artifactKey]);
            $artifact = [
                'url' => $url,
                'has_payload' => $payloads[$artifactKey] !== null,
            ];

            if ($artifactDirectory !== null) {
                $artifact['path'] = str_replace(DIRECTORY_SEPARATOR, '/', $request['relative_path']);
            }

            data_set($artifacts, $artifactKey, $artifact);
        }

        return $payloads;
    }

    private function defaultBatchConcurrency(): int
    {
        return max(1, (int) config('services.imdb.default_batch_concurrency', 5));
    }

    private function titleBatchConcurrency(): int
    {
        return max(
            1,
            (int) config('services.imdb.title_batch_concurrency', $this->defaultBatchConcurrency()),
        );
    }

    private function normalizeConcurrentPayload(
        string $url,
        string $artifactKey,
        ?array $payload,
        ?string $itemsKey,
        bool $nullable,
    ): ?array {
        if ($payload === null) {
            return null;
        }

        if ($itemsKey !== null) {
            return $this->fetchImdbJsonAction->completePagination($url, $itemsKey, $payload, $nullable);
        }

        if (! $this->hasNextPageToken($payload)) {
            return $payload;
        }

        $detectedItemsKey = $this->detectPaginatedItemsKey($payload, $artifactKey);

        if ($detectedItemsKey === null) {
            return $payload;
        }

        return $this->fetchImdbJsonAction->completePagination($url, $detectedItemsKey, $payload, $nullable);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function hasNextPageToken(array $payload): bool
    {
        $nextPageToken = data_get($payload, 'nextPageToken');

        return is_string($nextPageToken) && trim($nextPageToken) !== '';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function detectPaginatedItemsKey(array $payload, string $artifactKey): ?string
    {
        $hint = $this->artifactItemsKeyHint($artifactKey);

        if ($hint !== null && $this->payloadHasListAtKey($payload, $hint)) {
            return $hint;
        }

        foreach ($payload as $key => $value) {
            if (is_string($key) && $this->payloadHasListAtKey($payload, $key)) {
                return $key;
            }
        }

        return null;
    }

    private function artifactItemsKeyHint(string $artifactKey): ?string
    {
        $segment = Str::afterLast($artifactKey, '.');

        return $segment === '' ? null : $segment;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadHasListAtKey(array $payload, string $key): bool
    {
        $value = data_get($payload, $key);

        return is_array($value) && array_is_list($value);
    }

    private function resolveSourceUrl(string $imdbId, string $urlTemplate): string
    {
        return $this->resolveImdbApiUrlAction->handle($urlTemplate, [
            'titleId' => $imdbId,
            'id' => $imdbId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadHash(array $payload): string
    {
        return hash('sha256', json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(string $contents): array
    {
        try {
            $payload = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Existing JSON payload is invalid and cannot be reused.', previous: $exception);
        }

        if (! is_array($payload)) {
            throw new RuntimeException('Existing JSON payload is not an object.');
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $titlePayload
     */
    private function shouldDownloadSeriesArtifacts(array $titlePayload): bool
    {
        $imdbType = Str::lower($this->nullableString(data_get($titlePayload, 'type')) ?? '');

        if ($imdbType === '') {
            return false;
        }

        return in_array($imdbType, ['tvminiseries', 'tvpilot', 'tvseries', 'tvshortseries'], true)
            || str_contains($imdbType, 'series');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeObjectList(mixed $values): array
    {
        return collect(is_iterable($values) ? $values : [])
            ->filter(fn (mixed $value): bool => is_array($value))
            ->values()
            ->all();
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function writeJsonArtifact(?string $artifactDirectory, string $relativePath, mixed $payload): ?string
    {
        if ($artifactDirectory === null) {
            return null;
        }

        $path = $artifactDirectory.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ));

        return $path;
    }

    private function bundleStoragePath(?string $storageDirectory, string $imdbId): ?string
    {
        $storageDirectory = $this->normalizeStorageDirectory($storageDirectory);

        if ($storageDirectory === null) {
            return null;
        }

        return $storageDirectory.DIRECTORY_SEPARATOR.$imdbId.'.json';
    }

    private function artifactDirectory(?string $storageDirectory, string $imdbId): ?string
    {
        $storageDirectory = $this->normalizeStorageDirectory($storageDirectory);

        if ($storageDirectory === null) {
            return null;
        }

        return $storageDirectory.DIRECTORY_SEPARATOR.$imdbId;
    }

    private function normalizeStorageDirectory(?string $storageDirectory): ?string
    {
        if (! is_string($storageDirectory)) {
            return null;
        }

        $storageDirectory = rtrim($storageDirectory, DIRECTORY_SEPARATOR);

        return $storageDirectory === '' ? null : $storageDirectory;
    }
}
