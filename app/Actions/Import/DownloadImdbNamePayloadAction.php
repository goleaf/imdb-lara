<?php

namespace App\Actions\Import;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;
use Throwable;

class DownloadImdbNamePayloadAction
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
     *     storage_path: string|null
     * }
     */
    public function handle(string $imdbId, ?string $storageDirectory, bool $force = false): array
    {
        $storagePath = $this->bundleStoragePath($storageDirectory, $imdbId);
        $artifactDirectory = $this->artifactDirectory($storageDirectory, $imdbId);

        if ($storagePath !== null && ! $force && File::exists($storagePath)) {
            $existingPayload = $this->decodePayload(File::get($storagePath));

            if ($this->canReuseExistingBundle($existingPayload, $artifactDirectory)) {
                return [
                    'downloaded' => false,
                    'imdb_id' => $imdbId,
                    'payload' => $existingPayload,
                    'payload_hash' => $this->payloadHash($existingPayload),
                    'source_url' => $this->sourceUrl($imdbId),
                    'storage_path' => $storagePath,
                ];
            }
        }

        $sourceUrl = $this->sourceUrl($imdbId);
        $payload = $this->buildBundle($imdbId, $sourceUrl, $artifactDirectory);

        if ($storagePath !== null) {
            File::ensureDirectoryExists(dirname($storagePath));
            File::put($storagePath, json_encode(
                $payload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            ));
        }

        return [
            'downloaded' => true,
            'imdb_id' => $imdbId,
            'payload' => $payload,
            'payload_hash' => $this->payloadHash($payload),
            'source_url' => $sourceUrl,
            'storage_path' => $storagePath,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBundle(string $imdbId, string $sourceUrl, ?string $artifactDirectory): array
    {
        $artifacts = [];
        $downloadedAt = now()->toIso8601String();

        $details = $this->downloadArtifact(
            $artifactDirectory,
            'details.json',
            $sourceUrl,
            $artifacts,
            'details',
        );

        if (! is_array($details)) {
            throw new RuntimeException(sprintf('IMDb API returned an unexpected payload for name [%s].', $imdbId));
        }

        $artifactsPayload = $this->downloadArtifactsConcurrently($artifactDirectory, $artifacts, [
            [
                'artifact_key' => 'images',
                'relative_path' => 'images.json',
                'url' => $sourceUrl.'/images',
                'items_key' => 'images',
                'nullable' => true,
            ],
            [
                'artifact_key' => 'filmography',
                'relative_path' => 'filmography.json',
                'url' => $sourceUrl.'/filmography',
                'items_key' => 'credits',
                'nullable' => true,
            ],
            [
                'artifact_key' => 'relationships',
                'relative_path' => 'relationships.json',
                'url' => $sourceUrl.'/relationships',
                'items_key' => 'relationships',
                'nullable' => true,
            ],
            [
                'artifact_key' => 'trivia',
                'relative_path' => 'trivia.json',
                'url' => $sourceUrl.'/trivia',
                'items_key' => 'triviaEntries',
                'nullable' => true,
            ],
        ], $this->nameBatchConcurrency());
        $images = $artifactsPayload['images'] ?? null;
        $filmography = $artifactsPayload['filmography'] ?? null;
        $relationships = $artifactsPayload['relationships'] ?? null;
        $trivia = $artifactsPayload['trivia'] ?? null;

        $manifest = [
            'schemaVersion' => 1,
            'imdbId' => $imdbId,
            'downloadedAt' => $downloadedAt,
            'sourceUrl' => $sourceUrl,
            'artifacts' => $artifacts,
        ];

        $this->writeJsonArtifact($artifactDirectory, 'manifest.json', $manifest);

        return [
            'schemaVersion' => 1,
            'imdbId' => $imdbId,
            'downloadedAt' => $downloadedAt,
            'sourceUrl' => $sourceUrl,
            'details' => $details,
            'images' => $images,
            'filmography' => $filmography,
            'relationships' => $relationships,
            'trivia' => $trivia,
            'artifacts' => $artifacts,
        ];
    }

    private function sourceUrl(string $imdbId): string
    {
        return $this->resolveImdbApiUrlAction->handle(
            $this->resolveImdbApiUrlAction->endpoint('name'),
            ['nameId' => $imdbId],
        );
    }

    private function canReuseExistingBundle(array $payload, ?string $artifactDirectory): bool
    {
        if ($artifactDirectory === null) {
            return false;
        }

        return (int) data_get($payload, 'schemaVersion', 0) >= 1
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
                'IMDb optional name endpoint failed for [%s]; continuing without this artifact. %s',
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

    private function nameBatchConcurrency(): int
    {
        return max(
            1,
            (int) config(
                'services.imdb.name_batch_concurrency',
                config('services.imdb.default_batch_concurrency', 5),
            ),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
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
            throw new RuntimeException('Existing name JSON payload is invalid and cannot be reused.', previous: $exception);
        }

        if (! is_array($payload)) {
            throw new RuntimeException('Existing name JSON payload is not an object.');
        }

        return $payload;
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
