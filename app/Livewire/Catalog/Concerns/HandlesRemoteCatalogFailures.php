<?php

namespace App\Livewire\Catalog\Concerns;

use Illuminate\Pagination\Paginator;
use Throwable;

trait HandlesRemoteCatalogFailures
{
    /**
     * @template TViewData
     *
     * @param  callable(): TViewData  $resolver
     * @param  callable(Throwable): TViewData  $fallback
     * @return TViewData
     */
    protected function resolveRemoteCatalogViewData(callable $resolver, callable $fallback): mixed
    {
        try {
            return $resolver();
        } catch (Throwable $throwable) {
            if (! $this->remoteCatalogIsUnavailable($throwable)) {
                throw $throwable;
            }

            report($throwable);

            return $fallback($throwable);
        }
    }

    /**
     * @return array{
     *     emptyHeading: string,
     *     emptyText: string,
     *     isCatalogUnavailable: bool,
     *     statusHeading: string,
     *     statusText: string
     * }
     */
    protected function unavailableCatalogState(string $resourceLabel = 'catalog'): array
    {
        return [
            'emptyHeading' => 'Catalog temporarily unavailable.',
            'emptyText' => 'The imported IMDb catalog could not be reached. Try again in a few minutes.',
            'isCatalogUnavailable' => true,
            'statusHeading' => 'Live catalog temporarily unavailable',
            'statusText' => sprintf(
                'The live %s catalog is unavailable right now. Try again shortly.',
                $resourceLabel,
            ),
        ];
    }

    protected function emptyPaginator(int $perPage, string $pageName): Paginator
    {
        return new Paginator(
            items: collect(),
            perPage: $perPage,
            currentPage: 1,
            options: [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ],
        );
    }

    protected function remoteCatalogIsUnavailable(Throwable $throwable): bool
    {
        $message = $throwable->getMessage();

        return str_contains($message, 'max_connections_per_hour')
            || str_contains($message, 'SQLSTATE[HY000] [1226]')
            || str_contains($message, 'Connection refused')
            || str_contains($message, 'php_network_getaddresses')
            || str_contains($message, 'No route to host');
    }
}
