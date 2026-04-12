<?php

namespace App\Actions\Search;

use App\Actions\Catalog\BuildPublicInterestCategoryIndexQueryAction;
use App\Actions\Catalog\BuildPublicPeopleIndexQueryAction;
use App\Models\Person;
use App\Models\Title;
use Throwable;

class BuildSearchResultsViewDataAction
{
    public function __construct(
        private BuildPublicInterestCategoryIndexQueryAction $buildPublicInterestCategoryIndexQuery,
        private BuildPublicPeopleIndexQueryAction $buildPublicPeopleIndexQuery,
        private BuildSearchTitleResultsQueryAction $buildSearchTitleResultsQuery,
        private GetSearchFilterOptionsAction $getSearchFilterOptions,
        private ResolveSearchTopMatchAction $resolveSearchTopMatch,
        private PruneTopCatalogMatchAction $pruneTopCatalogMatch,
    ) {}

    /**
     * @param  array{
     *     country: ?string,
     *     genre: ?string,
     *     language: ?string,
     *     query: ?string,
     *     ratingMax: ?string,
     *     ratingMin: ?string,
     *     runtime: ?string,
     *     sort: string,
     *     status: ?string,
     *     theme: ?string,
     *     type: ?string,
     *     votesMin: ?string,
     *     yearFrom: ?string,
     *     yearTo: ?string
     * }  $filters
     * @return array<string, mixed>
     */
    public function handle(array $filters): array
    {
        $searchQuery = trim((string) ($filters['query'] ?? ''));
        $resultsPerLane = 12;
        $titleFilters = [
            'country' => $filters['country'] ?? null,
            'genre' => $filters['genre'] ?? null,
            'language' => $filters['language'] ?? null,
            'ratingMax' => $filters['ratingMax'] ?? null,
            'ratingMin' => $filters['ratingMin'] ?? null,
            'runtime' => $filters['runtime'] ?? null,
            'search' => $searchQuery,
            'sort' => $filters['sort'] ?? 'popular',
            'status' => $filters['status'] ?? null,
            'theme' => $filters['theme'] ?? null,
            'type' => $filters['type'] ?? null,
            'votesMin' => $filters['votesMin'] ?? null,
            'yearFrom' => $filters['yearFrom'] ?? null,
            'yearTo' => $filters['yearTo'] ?? null,
        ];

        $topTitleMatch = $this->buildSearchTitleResultsQuery->handle($titleFilters)->first();
        $titleResultsQuery = $this->buildSearchTitleResultsQuery->handle($titleFilters);

        $titles = $titleResultsQuery
            ->simplePaginate($resultsPerLane, pageName: 'titles')
            ->withQueryString();
        $titleResults = collect($titles->items());

        $people = collect();

        if (mb_strlen($searchQuery) >= 2 && (! Person::usesCatalogOnlySchema() || Person::catalogPeopleAvailable())) {
            try {
                $people = $this->buildPublicPeopleIndexQuery->handle([
                    'search' => $searchQuery,
                    'sort' => 'popular',
                ])->limit($resultsPerLane)->get();
            } catch (Throwable $throwable) {
                report($throwable);
            }
        }
        $interestCategories = mb_strlen($searchQuery) >= 2 && Title::usesCatalogOnlySchema()
            ? $this->buildPublicInterestCategoryIndexQuery->handle([
                'search' => $searchQuery,
                'sort' => 'popular',
            ])->limit(6)->get()
            : collect();

        $topMatch = $this->enrichTopMatch($this->resolveSearchTopMatch->handle(
            $searchQuery,
            $topTitleMatch,
            $people->first(),
        ));

        $visibleTitles = $this->pruneTopCatalogMatch->handle(
            $titleResults,
            $topMatch['type'] === 'title' ? $topMatch['record'] : null,
            $resultsPerLane,
        );
        $visiblePeople = $this->pruneTopCatalogMatch->handle(
            $people,
            $topMatch['type'] === 'person' ? $topMatch['record'] : null,
            $resultsPerLane,
        );

        $titles->setCollection($visibleTitles);

        return [
            'activeFilterCount' => $this->activeFilterCount($filters),
            'filterOptions' => $this->getSearchFilterOptions->handle(),
            'hasAnyResults' => $topMatch['record'] !== null
                || $visibleTitles->isNotEmpty()
                || $visiblePeople->isNotEmpty()
                || $interestCategories->isNotEmpty(),
            'interestCategories' => $interestCategories,
            'interestCategoryCount' => $interestCategories->count(),
            'people' => $visiblePeople,
            'peopleCount' => $visiblePeople->count(),
            'queryCopy' => $searchQuery !== ''
                ? 'MySQL-backed title, people, and theme matches, with title filters kept separate so the catalog remains easy to narrow down.'
                : 'Search the imported IMDb catalog across titles, people, and theme lanes, then refine title results by type, year, rating, runtime, language, and country.',
            'queryHeadline' => $searchQuery !== '' ? 'Results for “'.$searchQuery.'”' : 'Search the catalog',
            'searchLoadingTargets' => 'query,type,genre,theme,yearFrom,yearTo,ratingMin,ratingMax,votesMin,language,country,runtime,status,sort',
            'titleResultsCount' => $visibleTitles->count(),
            'titles' => $titles,
            'topMatch' => $topMatch,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function activeFilterCount(array $filters): int
    {
        return collect([
            $filters['type'] ?? null,
            $filters['genre'] ?? null,
            $filters['theme'] ?? null,
            $filters['yearFrom'] ?? null,
            $filters['yearTo'] ?? null,
            $filters['ratingMin'] ?? null,
            $filters['ratingMax'] ?? null,
            $filters['votesMin'] ?? null,
            $filters['language'] ?? null,
            $filters['country'] ?? null,
            $filters['runtime'] ?? null,
            $filters['status'] ?? null,
        ])->filter(fn (?string $value): bool => filled($value))->count();
    }

    /**
     * @param  array{record: Person|Title|null, type: 'person'|'title'|null}  $topMatch
     * @return array{
     *     record: Person|Title|null,
     *     type: 'person'|'title'|null,
     *     popularityRankLabel?: string|null,
     *     awardNominationsLabel?: string|null
     * }
     */
    private function enrichTopMatch(array $topMatch): array
    {
        if ($topMatch['type'] !== 'person' || ! $topMatch['record'] instanceof Person) {
            return $topMatch;
        }

        return [
            ...$topMatch,
            'popularityRankLabel' => $topMatch['record']->popularityRankBadgeLabel(),
            'awardNominationsLabel' => $topMatch['record']->awardNominationsBadgeLabel(),
        ];
    }
}
