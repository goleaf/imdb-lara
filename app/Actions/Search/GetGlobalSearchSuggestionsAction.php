<?php

namespace App\Actions\Search;

use App\Actions\Catalog\BuildPublicPeopleIndexQueryAction;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Database\Eloquent\Collection;

class GetGlobalSearchSuggestionsAction
{
    public function __construct(
        private BuildPublicPeopleIndexQueryAction $buildPublicPeopleIndexQuery,
        private BuildSearchTitleResultsQueryAction $buildSearchTitleResultsQuery,
    ) {}

    /**
     * @return array{
     *     people: Collection<int, Person>,
     *     titles: Collection<int, Title>
     * }
     */
    public function handle(?string $query, int $perGroup = 4): array
    {
        $query = trim((string) $query);

        if (mb_strlen($query) < 2) {
            return [
                'people' => new Collection,
                'titles' => new Collection,
            ];
        }

        $limit = max(1, min($perGroup, 6));

        return [
            'people' => $this->buildPublicPeopleIndexQuery
                ->handle([
                    'search' => $query,
                    'sort' => 'popular',
                ])
                ->limit($limit)
                ->get(),
            'titles' => $this->buildSearchTitleResultsQuery
                ->handle([
                    'search' => $query,
                    'sort' => 'popular',
                ])
                ->limit($limit)
                ->get(),
        ];
    }
}
