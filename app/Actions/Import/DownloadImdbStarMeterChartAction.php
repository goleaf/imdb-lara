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
     *     chart_path: string,
     *     manifest_path: string,
     *     names: list<array{imdb_id: string, path: string, payload: array<string, mixed>}>,
     *     source_url: string,
     *     storage_directory: string
     * }
     */
    public function handle(string $storageDirectory): array
    {
        $endpoint = $this->resolveImdbApiUrlAction->endpoint('chart.starmeter');

        if ($endpoint === '') {
            throw new RuntimeException('IMDb star meter chart endpoint is not configured.');
        }

        $sourceUrl = $this->resolveImdbApiUrlAction->handle($endpoint);
        $payload = $this->fetchImdbJsonAction->paginate($sourceUrl, 'names') ?? ['names' => []];
        $names = $this->normalizeNames(data_get($payload, 'names'));
        $downloadedAt = now()->toIso8601String();
        $chartDirectory = rtrim($storageDirectory, DIRECTORY_SEPARATOR);
        $namesDirectory = $chartDirectory.DIRECTORY_SEPARATOR.'names';
        $chartPath = $chartDirectory.DIRECTORY_SEPARATOR.'chart.json';
        $manifestPath = $chartDirectory.DIRECTORY_SEPARATOR.'manifest.json';

        File::ensureDirectoryExists($namesDirectory);
        File::put($chartPath, $this->encodeJson($payload));
        File::put($manifestPath, $this->encodeJson([
            'sourceUrl' => $sourceUrl,
            'downloadedAt' => $downloadedAt,
            'namesCount' => count($names),
            'chartPath' => 'chart.json',
        ]));

        $nameArtifacts = [];

        foreach ($names as $namePayload) {
            $imdbId = (string) data_get($namePayload, 'id');
            $namePath = $namesDirectory.DIRECTORY_SEPARATOR.$imdbId.'.json';
            $detailsPath = $namesDirectory.DIRECTORY_SEPARATOR.$imdbId.DIRECTORY_SEPARATOR.'details.json';

            File::ensureDirectoryExists(dirname($detailsPath));
            File::put($namePath, $this->encodeJson($namePayload));
            File::put($detailsPath, $this->encodeJson($namePayload));

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
}
