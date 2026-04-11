<?php

namespace App\Actions\Lists;

use App\Models\MovieRating;
use App\Models\Title;
use App\Models\UserList;
use Illuminate\Database\Eloquent\Collection;

class GetListTitleSuggestionsAction
{
    /**
     * @return Collection<int, Title>
     */
    public function handle(UserList $list, ?string $search, int $limit = 6): Collection
    {
        $search = trim((string) $search);

        if (mb_strlen($search) < 2) {
            return new Collection;
        }

        $query = Title::query()
            ->selectCatalogCardColumns()
            ->publishedCatalog()
            ->matchingSearch($search)
            ->whereDoesntHave('listItems', fn ($query) => $query->where('user_list_id', $list->id));

        if (Title::usesCatalogOnlySchema()) {
            $query->addSelect([
                'popularity_rank' => MovieRating::query()
                    ->select('vote_count')
                    ->whereColumn('movie_ratings.movie_id', 'movies.id')
                    ->limit(1),
            ]);
        }

        return $query
            ->when(
                Title::usesCatalogOnlySchema(),
                fn ($titleQuery) => $titleQuery->orderByDesc('popularity_rank'),
                fn ($titleQuery) => $titleQuery->orderBy('popularity_rank'),
            )
            ->orderByCatalogName()
            ->limit(max(1, min($limit, 8)))
            ->get();
    }
}
