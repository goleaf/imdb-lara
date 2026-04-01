<?php

namespace App\Actions\Search;

use App\Enums\TitleType;
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
            ->published()
            ->matchingSearch($search)
            ->where('title_type', '!=', TitleType::Episode)
            ->orderBy('popularity_rank')
            ->orderBy('name')
            ->limit(max(1, min($limit, 8)))
            ->get();
    }
}
