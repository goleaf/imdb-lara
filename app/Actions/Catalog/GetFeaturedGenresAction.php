<?php

namespace App\Actions\Catalog;

use App\Models\Genre;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class GetFeaturedGenresAction
{
    /**
     * @return Collection<int, Genre>
     */
    public function handle(int $limit = 8): Collection
    {
        return Cache::remember(
            "catalog:featured-genres:{$limit}",
            now()->addMinutes(10),
            fn (): Collection => Genre::query()
                ->select(['id', 'name', 'slug'])
                ->whereHas('titles', fn (Builder $titleQuery) => $titleQuery->publishedCatalog())
                ->withCount([
                    'titles as published_titles_count' => fn (Builder $titleQuery) => $titleQuery->publishedCatalog(),
                ])
                ->orderByDesc('published_titles_count')
                ->orderBy('name')
                ->limit($limit)
                ->get(),
        );
    }
}
