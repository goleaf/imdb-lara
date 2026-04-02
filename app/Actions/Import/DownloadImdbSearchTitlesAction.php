<?php

namespace App\Actions\Import;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

class DownloadImdbSearchTitlesAction
{
    public function __construct(
        private readonly FetchImdbJsonAction $fetchImdbJsonAction,
        private readonly ResolveImdbApiUrlAction $resolveImdbApiUrlAction,
    ) {}

    /**
     * @return array{
     *     manifest_path: string,
     *     query: string,
     *     search_path: string,
     *     source_url: string,
     *     storage_directory: string,
     *     titles: list<array{imdb_id: string, path: string, payload: array<string, mixed>}>
     * }
     */
    public function handle(string $query, ?int $limit, string $storageDirectory): array
    {
        $query = trim($query);

        if ($query === '') {
            throw new RuntimeException('IMDb search query cannot be empty.');
        }

        $endpoint = $this->resolveImdbApiUrlAction->endpoint('search.titles');

        if ($endpoint === '') {
            throw new RuntimeException('IMDb search titles endpoint is not configured.');
        }

        $queryParameters = array_filter([
            'query' => $query,
            'limit' => $limit,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
        $sourceUrl = $this->withQueryString(
            $this->resolveImdbApiUrlAction->handle($endpoint),
            $queryParameters,
        );
        $payload = $this->fetchImdbJsonAction->paginate($sourceUrl, 'titles') ?? ['titles' => []];
        $titles = $this->normalizeTitles(data_get($payload, 'titles'));
        $downloadedAt = now()->toIso8601String();
        $searchDirectory = rtrim($storageDirectory, DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR
            .$this->directoryName($query, $limit);
        $titlesDirectory = $searchDirectory.DIRECTORY_SEPARATOR.'titles';
        $searchPath = $searchDirectory.DIRECTORY_SEPARATOR.'search.json';
        $manifestPath = $searchDirectory.DIRECTORY_SEPARATOR.'manifest.json';

        File::ensureDirectoryExists($titlesDirectory);
        File::put($searchPath, $this->encodeJson($payload));
        File::put($manifestPath, $this->encodeJson([
            'query' => $query,
            'limit' => $limit,
            'sourceUrl' => $sourceUrl,
            'downloadedAt' => $downloadedAt,
            'titlesCount' => count($titles),
            'searchPath' => 'search.json',
        ]));

        $titleArtifacts = [];

        foreach ($titles as $titlePayload) {
            $imdbId = (string) data_get($titlePayload, 'id');
            $path = $titlesDirectory.DIRECTORY_SEPARATOR.$imdbId.'.json';

            File::put($path, $this->encodeJson($titlePayload));

            $titleArtifacts[] = [
                'imdb_id' => $imdbId,
                'path' => $path,
                'payload' => $titlePayload,
            ];
        }

        return [
            'manifest_path' => $manifestPath,
            'query' => $query,
            'search_path' => $searchPath,
            'source_url' => $sourceUrl,
            'storage_directory' => $searchDirectory,
            'titles' => $titleArtifacts,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeTitles(mixed $titles): array
    {
        return collect(is_iterable($titles) ? $titles : [])
            ->filter(function (mixed $title): bool {
                return is_array($title)
                    && is_string(data_get($title, 'id'))
                    && preg_match('/^tt\d+$/', (string) data_get($title, 'id')) === 1;
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, scalar>  $queryParameters
     */
    private function withQueryString(string $url, array $queryParameters): string
    {
        if ($queryParameters === []) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.http_build_query($queryParameters);
    }

    private function directoryName(string $query, ?int $limit): string
    {
        $slug = Str::slug(Str::limit($query, 80, ''));
        $slug = $slug === '' ? 'search' : $slug;
        $hash = substr(hash('sha256', mb_strtolower($query).'|'.($limit ?? 'all')), 0, 12);

        return $slug.'-'.$hash;
    }

    private function encodeJson(mixed $payload): string
    {
        try {
            return json_encode(
                $payload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            throw new RuntimeException('Failed to encode IMDb search payload.', previous: $exception);
        }
    }
}
