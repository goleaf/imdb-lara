<?php

namespace App\Actions\Search;

use App\Enums\ListVisibility;
use App\Models\Person;
use App\Models\Title;
use App\Models\UserList;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class GetGlobalSearchSuggestionsAction
{
    /**
     * @return array{
     *     lists: Collection<int, UserList>,
     *     people: Collection<int, Person>,
     *     titles: Collection<int, Title>
     * }
     */
    public function handle(?string $query, int $perGroup = 4): array
    {
        $query = trim((string) $query);

        if (mb_strlen($query) < 2) {
            return [
                'lists' => new Collection,
                'people' => new Collection,
                'titles' => new Collection,
            ];
        }

        $limit = max(1, min($perGroup, 6));

        return [
            'lists' => UserList::query()
                ->select(['id', 'user_id', 'name', 'slug', 'description', 'visibility', 'is_watchlist'])
                ->custom()
                ->where('visibility', ListVisibility::Public)
                ->where(function (Builder $listQuery) use ($query): void {
                    $listQuery
                        ->where('name', 'like', '%'.$query.'%')
                        ->orWhere('slug', 'like', '%'.$query.'%')
                        ->orWhere('description', 'like', '%'.$query.'%')
                        ->orWhereHas('user', function (Builder $userQuery) use ($query): void {
                            $userQuery
                                ->where('name', 'like', '%'.$query.'%')
                                ->orWhere('username', 'like', '%'.$query.'%');
                        });
                })
                ->with(['user:id,name,username'])
                ->withCount('items')
                ->orderByDesc('items_count')
                ->orderBy('name')
                ->limit($limit)
                ->get(),
            'people' => Person::query()
                ->select([
                    'id',
                    'name',
                    'slug',
                    'alternate_names',
                    'known_for_department',
                    'search_keywords',
                    'popularity_rank',
                    'is_published',
                ])
                ->published()
                ->matchingSearch($query)
                ->orderBy('popularity_rank')
                ->orderBy('name')
                ->limit($limit)
                ->get(),
            'titles' => Title::query()
                ->select([
                    'id',
                    'name',
                    'slug',
                    'title_type',
                    'release_year',
                    'popularity_rank',
                    'is_published',
                ])
                ->publishedCatalog()
                ->matchingSearch($query)
                ->orderBy('popularity_rank')
                ->orderBy('name')
                ->limit($limit)
                ->get(),
        ];
    }
}
