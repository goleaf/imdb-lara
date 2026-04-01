<?php

namespace App\Actions\Catalog;

use App\Enums\TitleType;
use App\Models\Genre;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class GetFeaturedGenresAction
{
    /**
     * @return Collection<int, Genre>
     */
    public function handle(int $limit = 8): Collection
    {
        return Genre::query()
            ->select(['id', 'name', 'slug'])
            ->whereHas('titles', fn (Builder $titleQuery) => $titleQuery
                ->published()
                ->where('title_type', '!=', TitleType::Episode))
            ->withCount([
                'titles as published_titles_count' => fn (Builder $titleQuery) => $titleQuery
                    ->published()
                    ->where('title_type', '!=', TitleType::Episode),
            ])
            ->orderByDesc('published_titles_count')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }
}
