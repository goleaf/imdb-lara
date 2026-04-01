<?php

namespace App\Actions\Lists;

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
            ->whereDoesntHave('listItems', fn ($query) => $query->where('user_list_id', $list->id))
            ->orderBy('popularity_rank')
            ->orderBy('name')
            ->limit(max(1, min($limit, 8)))
            ->get();
    }
}
