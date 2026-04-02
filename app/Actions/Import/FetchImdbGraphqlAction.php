<?php

namespace App\Actions\Import;

use Illuminate\Support\Facades\Cache;
use RuntimeException;

class FetchImdbGraphqlAction
{
    private const TITLE_CORE_SELECTION_SET = <<<'GRAPHQL'
            credits {
              category
              characters
              name {
                id
                display_name
                avatars {
                  url
                  width
                  height
                }
              }
            }
            certificates {
              rating
              country {
                code
                name
              }
            }
        GRAPHQL;

    private const TITLE_CORE_QUERY = <<<'GRAPHQL'
        query FetchTitleCore($id: ID!) {
          title(id: $id) {
        GRAPHQL
        .self::TITLE_CORE_SELECTION_SET.
        <<<'GRAPHQL'
          }
        }
        GRAPHQL;

    /**
     * @var array<string, array{
     *     certificates: array{certificates: list<array<string, mixed>>},
     *     credits: array{credits: list<array<string, mixed>>, totalCount: int}
     * }>
     */
    private static array $titleCoreMemoryCache = [];

    public function __construct(
        private readonly FetchImdbJsonAction $fetchImdbJsonAction,
    ) {}

    public function enabled(): bool
    {
        return (bool) config('services.imdb.graphql.enabled', false);
    }

    public static function flushMemoryCache(): void
    {
        self::$titleCoreMemoryCache = [];
    }

    /**
     * @return array{
     *     certificates: array{certificates: list<array<string, mixed>>},
     *     credits: array{credits: list<array<string, mixed>>, totalCount: int}
     * }
     */
    public function fetchTitleCore(string $imdbId): array
    {
        $imdbId = $this->normalizeTitleId($imdbId);
        $cachedPayload = $this->cachedTitleCore($imdbId);

        if ($cachedPayload !== null) {
            return $cachedPayload;
        }

        $payload = $this->fetchImdbJsonAction->post($this->url(), [
            'query' => self::TITLE_CORE_QUERY,
            'variables' => ['id' => $imdbId],
        ]);

        return $this->rememberTitleCore(
            $imdbId,
            $this->normalizeTitleCorePayload($imdbId, $payload, 'data.title'),
        );
    }

    /**
     * @param  list<string>  $imdbIds
     */
    public function preloadTitleCores(array $imdbIds): void
    {
        $normalizedIds = collect($imdbIds)
            ->map(fn (mixed $imdbId): string => $this->normalizeTitleId((string) $imdbId))
            ->unique()
            ->values()
            ->all();

        if ($normalizedIds === []) {
            return;
        }

        $missingIds = collect($normalizedIds)
            ->reject(fn (string $imdbId): bool => $this->cachedTitleCore($imdbId) !== null)
            ->values()
            ->all();

        if ($missingIds === []) {
            return;
        }

        foreach (array_chunk($missingIds, $this->batchSize()) as $chunk) {
            $aliasMap = [];
            $query = $this->buildBatchTitleCoreQuery($chunk, $aliasMap);
            $payload = $this->fetchImdbJsonAction->post($this->url(), ['query' => $query]);
            $data = data_get($payload, 'data');

            $this->throwOnGraphqlErrors($payload, implode(', ', $chunk));

            if (! is_array($data)) {
                throw new RuntimeException(sprintf(
                    'IMDb GraphQL returned no data payload for titles [%s].',
                    implode(', ', $chunk),
                ));
            }

            foreach ($aliasMap as $alias => $imdbId) {
                $titlePayload = $data[$alias] ?? null;

                if (! is_array($titlePayload)) {
                    throw new RuntimeException(sprintf(
                        'IMDb GraphQL returned no title payload for [%s].',
                        $imdbId,
                    ));
                }

                $this->rememberTitleCore($imdbId, $this->mapTitleCore($titlePayload));
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     certificates: array{certificates: list<array<string, mixed>>},
     *     credits: array{credits: list<array<string, mixed>>, totalCount: int}
     * }
     */
    private function normalizeTitleCorePayload(string $imdbId, array $payload, string $path): array
    {
        $this->throwOnGraphqlErrors($payload, $imdbId);

        $title = data_get($payload, $path);

        if (! is_array($title)) {
            throw new RuntimeException(sprintf('IMDb GraphQL returned no title payload for [%s].', $imdbId));
        }

        return $this->mapTitleCore($title);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     certificates: array{certificates: list<array<string, mixed>>},
     *     credits: array{credits: list<array<string, mixed>>, totalCount: int}
     * }
     */
    private function mapTitleCore(array $payload): array
    {
        $credits = $this->normalizeObjectList(data_get($payload, 'credits'));
        $certificates = $this->normalizeObjectList(data_get($payload, 'certificates'));

        return [
            'credits' => [
                'credits' => array_values(array_filter(array_map(
                    fn (array $credit): ?array => $this->mapCredit($credit),
                    $credits,
                ))),
                'totalCount' => count($credits),
            ],
            'certificates' => [
                'certificates' => array_values(array_filter(array_map(
                    fn (array $certificate): ?array => $this->mapCertificate($certificate),
                    $certificates,
                ))),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function throwOnGraphqlErrors(array $payload, string $subject): void
    {
        $errors = data_get($payload, 'errors');

        if (! is_array($errors) || $errors === []) {
            return;
        }

        throw new RuntimeException(sprintf(
            'IMDb GraphQL returned errors for title [%s]: %s',
            $subject,
            collect($errors)
                ->map(fn (mixed $error): string => is_array($error)
                    ? (string) ($error['message'] ?? json_encode($error, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
                    : (string) $error)
                ->implode('; '),
        ));
    }

    /**
     * @param  list<string>  $imdbIds
     * @param  array<string, string>  $aliasMap
     */
    private function buildBatchTitleCoreQuery(array $imdbIds, array &$aliasMap): string
    {
        $fields = collect($imdbIds)
            ->values()
            ->map(function (string $imdbId, int $index) use (&$aliasMap): string {
                $alias = 'title_'.$index;
                $aliasMap[$alias] = $imdbId;

                return sprintf(
                    "%s: title(id: \"%s\") {\n%s\n}",
                    $alias,
                    $imdbId,
                    self::TITLE_CORE_SELECTION_SET,
                );
            })
            ->implode("\n");

        return sprintf("query FetchTitleCoreBatch {\n%s\n}", $fields);
    }

    /**
     * @return array{
     *     certificates: array{certificates: list<array<string, mixed>>},
     *     credits: array{credits: list<array<string, mixed>>, totalCount: int}
     * }|null
     */
    private function cachedTitleCore(string $imdbId): ?array
    {
        if (array_key_exists($imdbId, self::$titleCoreMemoryCache)) {
            return self::$titleCoreMemoryCache[$imdbId];
        }

        if (! $this->httpCacheEnabled()) {
            return null;
        }

        $cachedPayload = Cache::get($this->titleCoreCacheKey($imdbId));

        if (! is_array($cachedPayload)) {
            return null;
        }

        self::$titleCoreMemoryCache[$imdbId] = $cachedPayload;

        return $cachedPayload;
    }

    /**
     * @param  array{
     *     certificates: array{certificates: list<array<string, mixed>>},
     *     credits: array{credits: list<array<string, mixed>>, totalCount: int}
     * }  $payload
     * @return array{
     *     certificates: array{certificates: list<array<string, mixed>>},
     *     credits: array{credits: list<array<string, mixed>>, totalCount: int}
     * }
     */
    private function rememberTitleCore(string $imdbId, array $payload): array
    {
        self::$titleCoreMemoryCache[$imdbId] = $payload;

        if ($this->httpCacheEnabled()) {
            Cache::put(
                $this->titleCoreCacheKey($imdbId),
                $payload,
                now()->addSeconds($this->httpCacheTtlSeconds()),
            );
        }

        return $payload;
    }

    private function titleCoreCacheKey(string $imdbId): string
    {
        return 'imdb:graphql:title-core:'.$imdbId;
    }

    private function normalizeTitleId(string $imdbId): string
    {
        $imdbId = trim($imdbId);

        if (preg_match('/^tt\d+$/', $imdbId) !== 1) {
            throw new RuntimeException(sprintf('Invalid IMDb title id [%s] for GraphQL fetch.', $imdbId));
        }

        return $imdbId;
    }

    private function url(): string
    {
        return (string) config('services.imdb.graphql.url', 'https://graph.imdbapi.dev/v1');
    }

    /**
     * @param  array<string, mixed>  $credit
     * @return array<string, mixed>|null
     */
    private function mapCredit(array $credit): ?array
    {
        $name = data_get($credit, 'name');

        if (! is_array($name)) {
            return null;
        }

        $nameId = $this->nullableString(data_get($name, 'id'));
        $displayName = $this->nullableString(data_get($name, 'display_name'));

        if ($nameId === null || $displayName === null) {
            return null;
        }

        $avatar = $this->normalizeObjectList(data_get($name, 'avatars'))[0] ?? null;
        $category = $this->nullableString(data_get($credit, 'category'));

        return array_filter([
            'name' => array_filter([
                'id' => $nameId,
                'displayName' => $displayName,
                'primaryProfessions' => $category === null ? [] : [$category],
                'primaryImage' => is_array($avatar)
                    ? array_filter([
                        'url' => $this->nullableString(data_get($avatar, 'url')),
                        'width' => $this->nullableInt(data_get($avatar, 'width')),
                        'height' => $this->nullableInt(data_get($avatar, 'height')),
                    ], fn (mixed $value): bool => $value !== null)
                    : null,
            ], fn (mixed $value): bool => $value !== null),
            'category' => $category,
            'characters' => $this->normalizeStringList(data_get($credit, 'characters')),
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $certificate
     * @return array<string, mixed>|null
     */
    private function mapCertificate(array $certificate): ?array
    {
        $rating = $this->nullableString(data_get($certificate, 'rating'));
        $country = data_get($certificate, 'country');

        if ($rating === null && ! is_array($country)) {
            return null;
        }

        return array_filter([
            'rating' => $rating,
            'country' => is_array($country)
                ? array_filter([
                    'code' => $this->nullableString(data_get($country, 'code')),
                    'name' => $this->nullableString(data_get($country, 'name')),
                ], fn (mixed $value): bool => $value !== null)
                : null,
        ], fn (mixed $value): bool => $value !== null);
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

    /**
     * @return list<string>
     */
    private function normalizeStringList(mixed $values): array
    {
        return collect(is_iterable($values) ? $values : [])
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => trim($value))
            ->values()
            ->all();
    }

    private function batchSize(): int
    {
        return max(1, (int) config('services.imdb.title_batch_concurrency', 5));
    }

    private function httpCacheEnabled(): bool
    {
        return (bool) config('services.imdb.http_cache.enabled', false);
    }

    private function httpCacheTtlSeconds(): int
    {
        return max(0, (int) config('services.imdb.http_cache.ttl_seconds', 86400));
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_int($value) ? $value : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
