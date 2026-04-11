<?php

namespace App\Actions\Search;

use App\Actions\Catalog\BuildPublicPeopleIndexQueryAction;

class BuildSearchResultsViewDataAction
{
    public function __construct(
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

        $people = mb_strlen($searchQuery) >= 2
            ? $this->buildPublicPeopleIndexQuery->handle([
                'search' => $searchQuery,
                'sort' => 'popular',
            ])->limit($resultsPerLane)->get()
            : collect();

        $topMatch = $this->resolveSearchTopMatch->handle(
            $searchQuery,
            $topTitleMatch,
            $people->first(),
        );

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
            'hasAnyResults' => $topMatch['record'] !== null || $visibleTitles->isNotEmpty() || $visiblePeople->isNotEmpty(),
            'people' => $visiblePeople,
            'peopleCount' => $visiblePeople->count(),
            'queryCopy' => $searchQuery !== ''
                ? 'MySQL-backed title and people matches, with title filters kept separate so the catalog remains easy to narrow down.'
                : 'Search the imported IMDb catalog, then refine title results by type, year, rating, runtime, language, and country.',
            'queryHeadline' => $searchQuery !== '' ? 'Results for “'.$searchQuery.'”' : 'Search the catalog',
            'searchLoadingTargets' => 'query,type,genre,yearFrom,yearTo,ratingMin,ratingMax,votesMin,language,country,runtime,status,sort',
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
}
