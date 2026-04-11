<?php

namespace App\Actions\Import;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

class DownloadImdbInterestPayloadAction
{
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
        $sourceUrl = $this->resolveImdbApiUrlAction->handle(
            $this->resolveImdbApiUrlAction->endpoint('interest'),
            ['interestId' => $imdbId],
        );

        if ($storagePath !== null && ! $force && File::exists($storagePath)) {
            $existingPayload = $this->decodePayload(File::get($storagePath));

            if ($this->canReuseExistingBundle($existingPayload, $artifactDirectory)) {
                return [
                    'downloaded' => false,
                    'imdb_id' => $imdbId,
                    'payload' => $existingPayload,
                    'payload_hash' => $this->payloadHash($existingPayload),
                    'source_url' => $sourceUrl,
                    'storage_path' => $storagePath,
                ];
            }
        }

        $downloadedAt = now()->toIso8601String();
        $interest = $this->fetchImdbJsonAction->get($sourceUrl);

        if (is_array($interest) && $this->hasNextPageToken($interest)) {
            $itemsKey = $this->detectPaginatedItemsKey($interest, 'interest');

            if ($itemsKey !== null) {
                $interest = $this->fetchImdbJsonAction->paginate($sourceUrl, $itemsKey);
            }
        }

        $payload = [
            'schemaVersion' => 1,
            'imdbId' => $imdbId,
            'downloadedAt' => $downloadedAt,
            'sourceUrl' => $sourceUrl,
            'interest' => $interest,
            'artifacts' => [
                'interest' => [
                    'path' => 'interest.json',
                    'url' => $sourceUrl,
                    'has_payload' => true,
                ],
            ],
        ];

        if ($artifactDirectory === null) {
            unset($payload['artifacts']['interest']['path']);
        } else {
            $this->writeJsonArtifact($artifactDirectory, 'interest.json', $interest);
            $this->writeJsonArtifact($artifactDirectory, 'manifest.json', [
                'schemaVersion' => 1,
                'imdbId' => $imdbId,
                'downloadedAt' => $downloadedAt,
                'sourceUrl' => $sourceUrl,
                'artifacts' => $payload['artifacts'],
            ]);
        }

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

    private function canReuseExistingBundle(array $payload, ?string $artifactDirectory): bool
    {
        if ($artifactDirectory === null) {
            return false;
        }

        return (int) data_get($payload, 'schemaVersion', 0) >= 1
            && File::exists($artifactDirectory.DIRECTORY_SEPARATOR.'manifest.json');
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
            throw new RuntimeException('Existing interest JSON payload is invalid and cannot be reused.', previous: $exception);
        }

        if (! is_array($payload)) {
            throw new RuntimeException('Existing interest JSON payload is not an object.');
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
