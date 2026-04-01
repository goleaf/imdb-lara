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
     *     storage_path: string
     * }
     */
    public function handle(string $imdbId, string $storageDirectory, bool $force = false): array
    {
        $storagePath = rtrim($storageDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$imdbId.'.json';
        $artifactDirectory = rtrim($storageDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$imdbId;

        File::ensureDirectoryExists($storageDirectory);

        if (! $force && File::exists($storagePath)) {
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

        File::put($storagePath, json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ));

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
    private function buildBundle(string $imdbId, string $sourceUrl, string $artifactDirectory): array
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

        $images = $this->downloadArtifact(
            $artifactDirectory,
            'images.json',
            $sourceUrl.'/images',
            $artifacts,
            'images',
            itemsKey: 'images',
            nullable: true,
        );
        $filmography = $this->downloadArtifact(
            $artifactDirectory,
            'filmography.json',
            $sourceUrl.'/filmography',
            $artifacts,
            'filmography',
            itemsKey: 'credits',
            nullable: true,
        );
        $relationships = $this->downloadArtifact(
            $artifactDirectory,
            'relationships.json',
            $sourceUrl.'/relationships',
            $artifacts,
            'relationships',
            itemsKey: 'relationships',
            nullable: true,
        );
        $trivia = $this->downloadArtifact(
            $artifactDirectory,
            'trivia.json',
            $sourceUrl.'/trivia',
            $artifacts,
            'trivia',
            itemsKey: 'triviaEntries',
            nullable: true,
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

    private function canReuseExistingBundle(array $payload, string $artifactDirectory): bool
    {
        return (int) data_get($payload, 'schemaVersion', 0) >= 1
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
                'IMDb optional name endpoint failed for [%s]; continuing without this artifact. %s',
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
