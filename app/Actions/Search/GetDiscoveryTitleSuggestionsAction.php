<?php

namespace App\Actions\Search;

use App\Models\Title;
use Illuminate\Database\Eloquent\Collection;

class GetDiscoveryTitleSuggestionsAction
{
    /**
     * @return Collection<int, Title>
     */
    public function handle(?string $search, int $limit = 6): Collection
    {
        $search = trim((string) $search);

        if (mb_strlen($search) < 2) {
            return new Collection;
        }

        return Title::query()
            ->select([
                'id',
                'name',
                'slug',
                'title_type',
                'release_year',
                'popularity_rank',
            ])
            ->publishedCatalog()
            ->matchingSearch($search)
            ->orderBy('popularity_rank')
            ->orderBy('name')
            ->limit(max(1, min($limit, 8)))
            ->get();
    }
}
