<?php

namespace App\Actions\Import;

use Illuminate\Support\Facades\File;
use JsonException;
use RuntimeException;

class DownloadImdbStarMeterChartAction
{
    public function __construct(
        private readonly FetchImdbJsonAction $fetchImdbJsonAction,
        private readonly ResolveImdbApiUrlAction $resolveImdbApiUrlAction,
    ) {}

    /**
     * @return array{
     *     chart_path: string|null,
     *     manifest_path: string|null,
     *     names: list<array{imdb_id: string, path: string|null, payload: array<string, mixed>}>,
     *     source_url: string,
     *     storage_directory: string|null
     * }
     */
    public function handle(?string $storageDirectory = null): array
    {
        $endpoint = $this->resolveImdbApiUrlAction->endpoint('chart.starmeter');

        if ($endpoint === '') {
            throw new RuntimeException('IMDb star meter chart endpoint is not configured.');
        }

        $sourceUrl = $this->resolveImdbApiUrlAction->handle($endpoint);
        $payload = $this->fetchImdbJsonAction->paginate($sourceUrl, 'names') ?? ['names' => []];
        $names = $this->normalizeNames(data_get($payload, 'names'));
        $downloadedAt = now()->toIso8601String();
        $chartDirectory = $this->normalizeStorageDirectory($storageDirectory);
        $namesDirectory = $chartDirectory !== null ? $chartDirectory.DIRECTORY_SEPARATOR.'names' : null;
        $chartPath = $chartDirectory !== null ? $chartDirectory.DIRECTORY_SEPARATOR.'chart.json' : null;
        $manifestPath = $chartDirectory !== null ? $chartDirectory.DIRECTORY_SEPARATOR.'manifest.json' : null;

        if ($namesDirectory !== null && $chartPath !== null && $manifestPath !== null) {
            File::ensureDirectoryExists($namesDirectory);
            File::put($chartPath, $this->encodeJson($payload));
            File::put($manifestPath, $this->encodeJson([
                'sourceUrl' => $sourceUrl,
                'downloadedAt' => $downloadedAt,
                'namesCount' => count($names),
                'chartPath' => 'chart.json',
            ]));
        }

        $nameArtifacts = [];

        foreach ($names as $namePayload) {
            $imdbId = (string) data_get($namePayload, 'id');
            $namePath = $namesDirectory !== null ? $namesDirectory.DIRECTORY_SEPARATOR.$imdbId.'.json' : null;
            $detailsPath = $namesDirectory !== null ? $namesDirectory.DIRECTORY_SEPARATOR.$imdbId.DIRECTORY_SEPARATOR.'details.json' : null;

            if ($namePath !== null && $detailsPath !== null) {
                File::ensureDirectoryExists(dirname($detailsPath));
                File::put($namePath, $this->encodeJson($namePayload));
                File::put($detailsPath, $this->encodeJson($namePayload));
            }

            $nameArtifacts[] = [
                'imdb_id' => $imdbId,
                'path' => $namePath,
                'payload' => $namePayload,
            ];
        }

        return [
            'chart_path' => $chartPath,
            'manifest_path' => $manifestPath,
            'names' => $nameArtifacts,
            'source_url' => $sourceUrl,
            'storage_directory' => $chartDirectory,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeNames(mixed $names): array
    {
        return collect(is_iterable($names) ? $names : [])
            ->filter(function (mixed $name): bool {
                return is_array($name)
                    && is_string(data_get($name, 'id'))
                    && preg_match('/^nm\d+$/', (string) data_get($name, 'id')) === 1;
            })
            ->unique(fn (array $name): string => (string) data_get($name, 'id'))
            ->values()
            ->all();
    }

    private function encodeJson(mixed $payload): string
    {
        try {
            return json_encode(
                $payload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            throw new RuntimeException('Failed to encode IMDb star meter chart payload.', previous: $exception);
        }
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
