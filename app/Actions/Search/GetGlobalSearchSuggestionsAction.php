<?php

namespace App\Actions\Search;

use App\Actions\Catalog\BuildPublicInterestCategoryIndexQueryAction;
use App\Actions\Catalog\BuildPublicPeopleIndexQueryAction;
use App\Models\InterestCategory;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

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
    public function handle(?string $query, int $perGroup = 4, array $titleFilters = []): array
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
            'interestCategories' => Title::usesCatalogOnlySchema()
                ? $this->buildPublicInterestCategoryIndexQuery
                    ->handle([
                        'search' => $query,
                        'sort' => 'popular',
                    ])
                    ->limit($limit)
                    ->get()
                : new Collection,
            'people' => $this->resolvePeopleSuggestions($query, $limit),
            'titles' => $this->resolveTitleSuggestions($query, $limit, $titleFilters),
        ];
    }

    /**
     * @return Collection<int, Person>
     */
    private function resolvePeopleSuggestions(string $query, int $limit): Collection
    {
        if (Person::usesCatalogOnlySchema() && ! Person::catalogPeopleAvailable()) {
            return new Collection;
        }

        try {
            return $this->buildPublicPeopleIndexQuery
                ->handle([
                    'search' => $query,
                    'sort' => 'popular',
                ])
                ->limit($limit)
                ->get();
        } catch (Throwable $throwable) {
            report($throwable);

            return new Collection;
        }
    }

    /**
     * @param  array<string, mixed>  $titleFilters
     * @return Collection<int, Title>
     */
    private function resolveTitleSuggestions(string $query, int $limit, array $titleFilters): Collection
    {
        return $this->buildSearchTitleResultsQuery
            ->handle([
                ...$titleFilters,
                'search' => $query,
                'sort' => $titleFilters['sort'] ?? 'popular',
            ])
            ->limit($limit)
            ->get();
    }
}
