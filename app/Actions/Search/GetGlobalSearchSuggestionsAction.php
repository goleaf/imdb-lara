<?php

namespace App\Actions\Search;

use App\Models\Person;
use App\Models\Title;
use App\Models\UserList;
use Illuminate\Database\Eloquent\Collection;

class GetGlobalSearchSuggestionsAction
{
    public function __construct(
        protected BuildSearchPublicListsQueryAction $buildSearchPublicListsQuery,
    ) {}

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
            'lists' => $this->buildSearchPublicListsQuery
                ->handle($query)
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
                ->with([
                    'mediaAssets:id,mediable_type,mediable_id,kind,url,alt_text,is_primary,position,published_at',
                    'professions:id,person_id,profession,is_primary,sort_order',
                ])
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
                ->with([
                    'mediaAssets:id,mediable_type,mediable_id,kind,url,alt_text,is_primary,position,published_at',
                ])
                ->orderBy('popularity_rank')
                ->orderBy('name')
                ->limit($limit)
                ->get(),
        ];
    }
}
