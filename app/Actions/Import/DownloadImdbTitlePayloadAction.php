<?php

namespace App\Actions\Import;

use App\Models\ImdbTitleImport;
use Illuminate\Support\Facades\File;
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
        private readonly ResolveImdbApiUrlAction $resolveImdbApiUrlAction,
    ) {}

    /**
     * @return array{
     *     downloaded: bool,
     *     imdb_id: string,
     *     payload: array<string, mixed>,
     *     payload_hash: string,
     *     source_url: string,
     *     storage_path: string
     * }
     */
    public function handle(string $imdbId, string $storageDirectory, string $urlTemplate, bool $force = false): array
    {
        $storagePath = rtrim($storageDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$imdbId.'.json';
        $artifactDirectory = rtrim($storageDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$imdbId;

        File::ensureDirectoryExists($storageDirectory);

        if (! $force && File::exists($storagePath)) {
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

        File::put($storagePath, json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ));

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
     *     storage_path: string
     * }  $result
     */
    private function trackImportArtifact(array $result): void
    {
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
    private function buildBundle(string $imdbId, string $sourceUrl, string $artifactDirectory): array
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

        $credits = $this->downloadArtifact(
            $artifactDirectory,
            'credits.json',
            $this->titleEndpoint('title.credits', $imdbId),
            $artifacts,
            'credits',
            itemsKey: 'credits',
            nullable: true,
        );
        $releaseDates = $this->downloadArtifact(
            $artifactDirectory,
            'release-dates.json',
            $this->titleEndpoint('title.release_dates', $imdbId),
            $artifacts,
            'releaseDates',
            itemsKey: 'releaseDates',
            nullable: true,
        );
        $akas = $this->downloadArtifact(
            $artifactDirectory,
            'akas.json',
            $this->titleEndpoint('title.akas', $imdbId),
            $artifacts,
            'akas',
            itemsKey: 'akas',
            nullable: true,
        );
        $seasons = $this->downloadArtifact(
            $artifactDirectory,
            'seasons.json',
            $this->titleEndpoint('title.seasons', $imdbId),
            $artifacts,
            'seasons',
            itemsKey: 'seasons',
            nullable: true,
        );
        $episodes = $this->downloadArtifact(
            $artifactDirectory,
            'episodes.json',
            $this->titleEndpoint('title.episodes', $imdbId),
            $artifacts,
            'episodes',
            itemsKey: 'episodes',
            nullable: true,
        );
        $images = $this->downloadArtifact(
            $artifactDirectory,
            'images.json',
            $this->titleEndpoint('title.images', $imdbId),
            $artifacts,
            'images',
            itemsKey: 'images',
            nullable: true,
        );
        $videos = $this->downloadArtifact(
            $artifactDirectory,
            'videos.json',
            $this->titleEndpoint('title.videos', $imdbId),
            $artifacts,
            'videos',
            itemsKey: 'videos',
            nullable: true,
        );
        $awardNominations = $this->downloadArtifact(
            $artifactDirectory,
            'award-nominations.json',
            $this->titleEndpoint('title.award_nominations', $imdbId),
            $artifacts,
            'awardNominations',
            itemsKey: 'awardNominations',
            nullable: true,
        );
        $parentsGuide = $this->downloadArtifact(
            $artifactDirectory,
            'parents-guide.json',
            $this->titleEndpoint('title.parents_guide', $imdbId),
            $artifacts,
            'parentsGuide',
            nullable: true,
        );
        $certificates = $this->downloadArtifact(
            $artifactDirectory,
            'certificates.json',
            $this->titleEndpoint('title.certificates', $imdbId),
            $artifacts,
            'certificates',
            itemsKey: 'certificates',
            nullable: true,
        );
        $companyCredits = $this->downloadArtifact(
            $artifactDirectory,
            'company-credits.json',
            $this->titleEndpoint('title.company_credits', $imdbId),
            $artifacts,
            'companyCredits',
            itemsKey: 'companyCredits',
            nullable: true,
        );
        $boxOffice = $this->downloadArtifact(
            $artifactDirectory,
            'box-office.json',
            $this->titleEndpoint('title.box_office', $imdbId),
            $artifacts,
            'boxOffice',
            nullable: true,
        );
        $names = $this->downloadNames(
            $this->collectNameIds($titlePayload, $credits, $awardNominations),
            $artifactDirectory,
            $artifacts,
        );
        $interests = $this->downloadInterests(
            $this->collectInterestIds($titlePayload),
            $artifactDirectory,
            $artifacts,
        );

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
            'names' => $names,
            'interests' => $interests,
            'artifacts' => $artifacts,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $credits
     * @param  array<string, mixed>|null  $awardNominations
     * @return list<string>
     */
    private function collectNameIds(array $titlePayload, ?array $credits, ?array $awardNominations): array
    {
        $ids = [];

        foreach (['directors', 'writers', 'stars'] as $key) {
            foreach ($this->normalizeObjectList(data_get($titlePayload, $key)) as $personPayload) {
                $imdbId = $this->nullableString(data_get($personPayload, 'id'));

                if ($imdbId !== null) {
                    $ids[] = $imdbId;
                }
            }
        }

        foreach ($this->normalizeObjectList(data_get($credits, 'credits')) as $creditPayload) {
            $imdbId = $this->nullableString(data_get($creditPayload, 'name.id'));

            if ($imdbId !== null) {
                $ids[] = $imdbId;
            }
        }

        foreach ($this->normalizeObjectList(data_get($awardNominations, 'awardNominations')) as $nominationPayload) {
            foreach ($this->normalizeObjectList(data_get($nominationPayload, 'nominees')) as $nomineePayload) {
                $imdbId = $this->nullableString(data_get($nomineePayload, 'id'));

                if ($imdbId !== null) {
                    $ids[] = $imdbId;
                }
            }
        }

        return collect($ids)
            ->filter(fn (mixed $value): bool => is_string($value) && preg_match('/^nm\d+$/', $value) === 1)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $nameIds
     * @return array<string, array<string, mixed>>
     */
    private function downloadNames(array $nameIds, string $artifactDirectory, array &$artifacts): array
    {
        $names = [];

        foreach ($nameIds as $nameId) {
            $nameUrl = $this->resolveImdbApiUrlAction->handle(
                $this->resolveImdbApiUrlAction->endpoint('name'),
                ['nameId' => $nameId],
            );

            $names[$nameId] = array_filter([
                'details' => $this->downloadArtifact(
                    $artifactDirectory,
                    'names/'.$nameId.'/details.json',
                    $nameUrl,
                    $artifacts,
                    'names.'.$nameId.'.details',
                    nullable: true,
                ),
                'images' => $this->downloadArtifact(
                    $artifactDirectory,
                    'names/'.$nameId.'/images.json',
                    $nameUrl.'/images',
                    $artifacts,
                    'names.'.$nameId.'.images',
                    itemsKey: 'images',
                    nullable: true,
                ),
                'relationships' => $this->downloadArtifact(
                    $artifactDirectory,
                    'names/'.$nameId.'/relationships.json',
                    $nameUrl.'/relationships',
                    $artifacts,
                    'names.'.$nameId.'.relationships',
                    nullable: true,
                ),
                'trivia' => $this->downloadArtifact(
                    $artifactDirectory,
                    'names/'.$nameId.'/trivia.json',
                    $nameUrl.'/trivia',
                    $artifacts,
                    'names.'.$nameId.'.trivia',
                    itemsKey: 'triviaEntries',
                    nullable: true,
                ),
            ], fn (mixed $value): bool => $value !== null);
        }

        return $names;
    }

    /**
     * @param  list<string>  $interestIds
     * @return array<string, array<string, mixed>>
     */
    private function downloadInterests(array $interestIds, string $artifactDirectory, array &$artifacts): array
    {
        $interests = [];

        foreach ($interestIds as $interestId) {
            $interestUrl = $this->resolveImdbApiUrlAction->handle(
                $this->resolveImdbApiUrlAction->endpoint('interest'),
                ['interestId' => $interestId],
            );
            $interestPayload = $this->downloadArtifact(
                $artifactDirectory,
                'interests/'.$interestId.'.json',
                $interestUrl,
                $artifacts,
                'interests.'.$interestId,
                nullable: true,
            );

            if ($interestPayload !== null) {
                $interests[$interestId] = $interestPayload;
            }
        }

        return $interests;
    }

    private function titleEndpoint(string $endpointKey, string $imdbId): string
    {
        return $this->resolveImdbApiUrlAction->handle(
            $this->resolveImdbApiUrlAction->endpoint($endpointKey),
            ['titleId' => $imdbId],
        );
    }

    private function canReuseExistingBundle(array $payload, string $artifactDirectory): bool
    {
        return (int) data_get($payload, 'schemaVersion', 0) >= 3
            && File::exists($artifactDirectory.DIRECTORY_SEPARATOR.'manifest.json');
    }

    /**
     * @param  array<string, mixed>  $artifacts
     * @return array<string, mixed>|null
     */
    private function downloadArtifact(
        string $artifactDirectory,
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
        data_set($artifacts, $artifactKey, [
            'path' => str_replace(DIRECTORY_SEPARATOR, '/', $relativePath),
            'url' => $url,
            'has_payload' => $payload !== null,
        ]);

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
     * @return list<string>
     */
    private function collectInterestIds(array $titlePayload): array
    {
        return collect($this->normalizeObjectList(data_get($titlePayload, 'interests')))
            ->map(fn (array $interestPayload): ?string => $this->nullableString(data_get($interestPayload, 'id')))
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->unique()
            ->values()
            ->all();
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

    private function writeJsonArtifact(string $artifactDirectory, string $relativePath, mixed $payload): string
    {
        $path = $artifactDirectory.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ));

        return $path;
    }
}
