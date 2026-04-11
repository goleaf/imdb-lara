<?php

namespace App\Actions\Catalog;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class LoadPublicTitleBrowserPageAction
{
    public function __construct(
        private BuildPublicTitleIndexQueryAction $buildPublicTitleIndexQuery,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     titles: Paginator,
     *     usingStaleCache: bool,
     *     isUnavailable: bool
     * }
     */
    public function handle(array $filters, int $perPage, string $pageName): array
    {
        $page = max(1, Paginator::resolveCurrentPage($pageName));
        $cacheKey = $this->cacheKey($filters, $page, $perPage);
        $staleCacheKey = $cacheKey.':stale';

        /** @var array{items: Collection<int, mixed>, hasMorePages: bool}|null $cachedPayload */
        $cachedPayload = Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            function () use ($filters, $page, $perPage, $staleCacheKey): array {
                $payload = $this->queryPagePayload($filters, $page, $perPage);

                Cache::put($staleCacheKey, $payload, now()->addHours(6));

                return $payload;
            },
        );

        return [
            'titles' => $this->makePaginator(
                $cachedPayload['items'],
                $perPage,
                $page,
                $pageName,
                $cachedPayload['hasMorePages'],
            ),
            'usingStaleCache' => false,
            'isUnavailable' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     titles: Paginator,
     *     usingStaleCache: bool,
     *     isUnavailable: bool
     * }
     */
    public function handleSafely(array $filters, int $perPage, string $pageName): array
    {
        $page = max(1, Paginator::resolveCurrentPage($pageName));
        $cacheKey = $this->cacheKey($filters, $page, $perPage);
        $staleCacheKey = $cacheKey.':stale';

        try {
            return $this->handle($filters, $perPage, $pageName);
        } catch (Throwable $exception) {
            if (! CatalogBackendUnavailable::matches($exception)) {
                throw $exception;
            }

            report($exception);

            /** @var array{items: Collection<int, mixed>, hasMorePages: bool}|null $stalePayload */
            $stalePayload = Cache::get($staleCacheKey);

            if ($stalePayload !== null) {
                Log::warning('Falling back to stale title browser cache after catalog query failure.', [
                    'page' => $page,
                    'page_name' => $pageName,
                ]);

                return [
                    'titles' => $this->makePaginator(
                        $stalePayload['items'],
                        $perPage,
                        $page,
                        $pageName,
                        $stalePayload['hasMorePages'],
                    ),
                    'usingStaleCache' => true,
                    'isUnavailable' => true,
                ];
            }

            return [
                'titles' => $this->makePaginator(
                    collect(),
                    $perPage,
                    $page,
                    $pageName,
                    false,
                ),
                'usingStaleCache' => false,
                'isUnavailable' => true,
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     titles: Collection<int, mixed>,
     *     usingStaleCache: bool,
     *     isUnavailable: bool
     * }
     */
    public function handleCollection(array $filters): array
    {
        $cacheKey = $this->collectionCacheKey($filters);
        $staleCacheKey = $cacheKey.':stale';

        /** @var Collection<int, mixed> $cachedPayload */
        $cachedPayload = Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            function () use ($filters, $staleCacheKey): Collection {
                $payload = $this->queryCollectionPayload($filters);

                Cache::put($staleCacheKey, $payload, now()->addHours(6));

                return $payload;
            },
        );

        return [
            'titles' => $cachedPayload,
            'usingStaleCache' => false,
            'isUnavailable' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     titles: Collection<int, mixed>,
     *     usingStaleCache: bool,
     *     isUnavailable: bool
     * }
     */
    public function handleCollectionSafely(array $filters): array
    {
        $cacheKey = $this->collectionCacheKey($filters);
        $staleCacheKey = $cacheKey.':stale';

        try {
            return $this->handleCollection($filters);
        } catch (Throwable $exception) {
            if (! CatalogBackendUnavailable::matches($exception)) {
                throw $exception;
            }

            report($exception);

            /** @var Collection<int, mixed>|null $stalePayload */
            $stalePayload = Cache::get($staleCacheKey);

            if ($stalePayload !== null) {
                Log::warning('Falling back to stale title browser collection cache after catalog query failure.', [
                    'filters' => $this->normalizeFilters($filters),
                ]);

                return [
                    'titles' => $stalePayload,
                    'usingStaleCache' => true,
                    'isUnavailable' => true,
                ];
            }

            return [
                'titles' => collect(),
                'usingStaleCache' => false,
                'isUnavailable' => true,
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     items: Collection<int, mixed>,
     *     hasMorePages: bool
     * }
     */
    private function queryPagePayload(array $filters, int $page, int $perPage): array
    {
        $results = $this->buildPublicTitleIndexQuery
            ->handle($filters)
            ->forPage($page, $perPage + 1)
            ->get();

        return [
            'items' => $results->take($perPage)->values(),
            'hasMorePages' => $results->count() > $perPage,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, mixed>
     */
    private function queryCollectionPayload(array $filters): Collection
    {
        return $this->buildPublicTitleIndexQuery
            ->handle($filters)
            ->get();
    }

    /**
     * @param  Collection<int, mixed>  $items
     */
    private function makePaginator(
        Collection $items,
        int $perPage,
        int $page,
        string $pageName,
        bool $hasMorePages,
    ): Paginator {
        $paginator = new Paginator($items, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);

        $paginator->hasMorePagesWhen($hasMorePages);

        return $paginator->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function cacheKey(array $filters, int $page, int $perPage): string
    {
        $normalizedFilters = $this->normalizeFilters($filters);

        return 'catalog:title-browser:'.sha1(json_encode([
            'filters' => $normalizedFilters,
            'page' => $page,
            'per_page' => $perPage,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function collectionCacheKey(array $filters): string
    {
        return 'catalog:title-browser:collection:'.sha1(json_encode([
            'filters' => $this->normalizeFilters($filters),
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function normalizeFilters(array $filters): array
    {
        ksort($filters);

        foreach ($filters as $key => $value) {
            if (is_array($value)) {
                sort($value);
                $filters[$key] = $value;
            }
        }

        return $filters;
    }
}
