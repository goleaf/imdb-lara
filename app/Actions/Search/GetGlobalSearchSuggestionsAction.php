<?php

namespace App\Actions\Search;

use App\Actions\Catalog\BuildPublicInterestCategoryIndexQueryAction;
use App\Actions\Catalog\BuildPublicPeopleIndexQueryAction;
use App\Models\InterestCategory;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Database\Eloquent\Collection;

class GetGlobalSearchSuggestionsAction
{
    public function __construct(
        private BuildPublicInterestCategoryIndexQueryAction $buildPublicInterestCategoryIndexQuery,
        private BuildPublicPeopleIndexQueryAction $buildPublicPeopleIndexQuery,
        private BuildSearchTitleResultsQueryAction $buildSearchTitleResultsQuery,
    ) {}

    /**
     * @return array{
     *     interestCategories: Collection<int, InterestCategory>,
     *     people: Collection<int, Person>,
     *     titles: Collection<int, Title>
     * }
     */
    public function handle(?string $query, int $perGroup = 4): array
    {
        $query = trim((string) $query);

        if (mb_strlen($query) < 2) {
            return [
                'interestCategories' => new Collection,
                'people' => new Collection,
                'titles' => new Collection,
            ];
        }

        $limit = max(1, min($perGroup, 6));

        return [
            'interestCategories' => $this->buildPublicInterestCategoryIndexQuery
                ->handle([
                    'search' => $query,
                    'sort' => 'popular',
                ])
                ->limit($limit)
                ->get(),
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
