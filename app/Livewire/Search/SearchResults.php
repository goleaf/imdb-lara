<?php

namespace App\Livewire\Search;

use App\Actions\Catalog\BuildPublicPeopleIndexQueryAction;
use App\Actions\Search\BuildSearchPublicListsQueryAction;
use App\Actions\Search\BuildSearchTitleResultsQueryAction;
use App\Actions\Search\GetSearchFilterOptionsAction;
use App\Models\Person;
use App\Models\Title;
use App\Models\UserList;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SearchResults extends Component
{
    use WithPagination;

    protected BuildPublicPeopleIndexQueryAction $buildPublicPeopleIndexQuery;

    protected BuildSearchTitleResultsQueryAction $buildSearchTitleResultsQuery;

    protected BuildSearchPublicListsQueryAction $buildSearchPublicListsQuery;

    protected GetSearchFilterOptionsAction $getSearchFilterOptions;

    #[Url(as: 'q')]
    public string $query = '';

    #[Url]
    public ?string $type = null;

    #[Url]
    public ?string $genre = null;

    #[Url]
    public ?string $yearFrom = null;

    #[Url]
    public ?string $yearTo = null;

    #[Url]
    public ?string $ratingMin = null;

    #[Url]
    public ?string $ratingMax = null;

    #[Url]
    public ?string $votesMin = null;

    #[Url]
    public ?string $language = null;

    #[Url]
    public ?string $country = null;

    #[Url]
    public ?string $runtime = null;

    #[Url]
    public ?string $status = null;

    #[Url]
    public string $sort = 'popular';

    public function boot(
        BuildPublicPeopleIndexQueryAction $buildPublicPeopleIndexQuery,
        BuildSearchTitleResultsQueryAction $buildSearchTitleResultsQuery,
        BuildSearchPublicListsQueryAction $buildSearchPublicListsQuery,
        GetSearchFilterOptionsAction $getSearchFilterOptions,
    ): void {
        $this->buildPublicPeopleIndexQuery = $buildPublicPeopleIndexQuery;
        $this->buildSearchTitleResultsQuery = $buildSearchTitleResultsQuery;
        $this->buildSearchPublicListsQuery = $buildSearchPublicListsQuery;
        $this->getSearchFilterOptions = $getSearchFilterOptions;
    }

    public function updated(string $property): void
    {
        if (! str_starts_with($property, 'paginators.')) {
            $this->resetPage(pageName: 'titles');
        }
    }

    public function clearTitleFilters(): void
    {
        $this->type = null;
        $this->genre = null;
        $this->yearFrom = null;
        $this->yearTo = null;
        $this->ratingMin = null;
        $this->ratingMax = null;
        $this->votesMin = null;
        $this->language = null;
        $this->country = null;
        $this->runtime = null;
        $this->status = null;
        $this->sort = 'popular';
        $this->resetPage(pageName: 'titles');
    }

    public function render()
    {
        $searchQuery = trim($this->query);
        $showGroupedMatches = mb_strlen($searchQuery) >= 2;

        $titleResultsQuery = $this->buildSearchTitleResultsQuery->handle([
            'country' => $this->country,
            'genre' => $this->genre,
            'language' => $this->language,
            'ratingMax' => $this->ratingMax,
            'ratingMin' => $this->ratingMin,
            'runtime' => $this->runtime,
            'search' => $searchQuery,
            'sort' => $this->sort,
            'status' => $this->status,
            'type' => $this->type,
            'votesMin' => $this->votesMin,
            'yearFrom' => $this->yearFrom,
            'yearTo' => $this->yearTo,
        ]);

        $titleResultsCount = (clone $titleResultsQuery)->count();
        $topTitleMatch = $showGroupedMatches ? (clone $titleResultsQuery)->first() : null;
        $titles = $titleResultsQuery
            ->simplePaginate(12, pageName: 'titles')
            ->withQueryString();

        $people = collect();
        $peopleCount = 0;
        $topPersonMatch = null;
        $lists = collect();
        $listsCount = 0;
        $topListMatch = null;

        if ($showGroupedMatches) {
            $peopleResultsQuery = $this->buildPublicPeopleIndexQuery->handle([
                'search' => $searchQuery,
                'sort' => 'popular',
            ]);
            $peopleCount = (clone $peopleResultsQuery)->count();
            $topPersonMatch = (clone $peopleResultsQuery)->first();
            $people = $peopleResultsQuery->limit(12)->get();

            $listsResultsQuery = $this->buildSearchPublicListsQuery->handle($searchQuery);
            $listsCount = (clone $listsResultsQuery)->count();
            $topListMatch = (clone $listsResultsQuery)->first();
            $lists = $listsResultsQuery->limit(12)->get();
        }

        $topMatch = $this->resolveTopMatch(
            $searchQuery,
            $topTitleMatch,
            $topPersonMatch,
            $topListMatch,
        );

        $trimmedQuery = $searchQuery;
        $filterOptions = $this->getSearchFilterOptions->handle();
        $filterOptions['sortOptions'] = collect($filterOptions['sortOptions'])
            ->map(fn (array $option): array => [
                ...$option,
                'icon' => match ($option['value']) {
                    'popular' => 'fire',
                    'trending' => 'bolt',
                    'rating' => 'star',
                    'latest' => 'clock',
                    'year' => 'calendar-days',
                    default => 'bars-arrow-down',
                },
            ])
            ->all();

        return view('livewire.search.search-results', [
            'activeFilterCount' => collect([
                $this->type,
                $this->genre,
                $this->yearFrom,
                $this->yearTo,
                $this->ratingMin,
                $this->ratingMax,
                $this->votesMin,
                $this->language,
                $this->country,
                $this->runtime,
                $this->status,
            ])->filter(fn (?string $value): bool => filled($value))->count(),
            'filterOptions' => $filterOptions,
            'hasAnyResults' => $titleResultsCount > 0 || $peopleCount > 0 || $listsCount > 0,
            'lists' => $lists,
            'listsCount' => $listsCount,
            'people' => $people,
            'peopleCount' => $peopleCount,
            'queryCopy' => $trimmedQuery !== ''
                ? 'Titles, people, and public lists are separated into clearer result lanes, with a featured top match and quieter title filters.'
                : 'Start with a title, series, person, or public list, then refine title results only when you need more precision.',
            'queryHeadline' => $trimmedQuery !== '' ? 'Results for “'.$trimmedQuery.'”' : 'Search the database',
            'searchLoadingTargets' => 'query,type,genre,yearFrom,yearTo,ratingMin,ratingMax,votesMin,language,country,runtime,status,sort',
            'secondaryMatch' => $topMatch['secondaryRecord'],
            'secondaryMatchType' => $topMatch['secondaryType'],
            'showGroupedMatches' => $showGroupedMatches,
            'topMatch' => $topMatch['record'],
            'topMatchType' => $topMatch['type'],
            'titleResultsCount' => $titleResultsCount,
            'titles' => $titles,
            'initialActiveTab' => $topMatch['type']
                ?? ($titleResultsCount > 0 ? 'titles' : ($peopleCount > 0 ? 'people' : ($listsCount > 0 ? 'lists' : 'titles'))),
        ]);
    }

    /**
     * @return array{
     *     type: 'list'|'person'|'title'|null,
     *     record: Person|Title|UserList|null,
     *     secondaryType: 'list'|'person'|'title'|null,
     *     secondaryRecord: Person|Title|UserList|null
     * }
     */
    private function resolveTopMatch(
        string $searchQuery,
        ?Title $topTitleMatch,
        ?Person $topPersonMatch,
        ?UserList $topListMatch,
    ): array {
        $candidates = collect([
            [
                'type' => 'title',
                'record' => $topTitleMatch,
                'score' => $topTitleMatch
                    ? $this->scoreMatchCandidate($searchQuery, [
                        $topTitleMatch->name,
                        $topTitleMatch->original_name,
                    ])
                    : null,
                'priority' => 0,
            ],
            [
                'type' => 'person',
                'record' => $topPersonMatch,
                'score' => $topPersonMatch
                    ? $this->scoreMatchCandidate($searchQuery, [
                        $topPersonMatch->name,
                        ...$this->splitAliases($topPersonMatch->alternate_names),
                    ])
                    : null,
                'priority' => 1,
            ],
            [
                'type' => 'list',
                'record' => $topListMatch,
                'score' => $topListMatch
                    ? $this->scoreMatchCandidate($searchQuery, [
                        $topListMatch->name,
                        $topListMatch->slug,
                        $topListMatch->description,
                    ])
                    : null,
                'priority' => 2,
            ],
        ])
            ->filter(fn (array $candidate): bool => $candidate['record'] !== null && $candidate['score'] !== null)
            ->sort(function (array $left, array $right): int {
                if ($left['score'] === $right['score']) {
                    return $left['priority'] <=> $right['priority'];
                }

                return $right['score'] <=> $left['score'];
            })
            ->values();

        if ($candidates->isEmpty()) {
            return [
                'type' => null,
                'record' => null,
                'secondaryType' => null,
                'secondaryRecord' => null,
            ];
        }

        /** @var array{type:'list'|'person'|'title',record:Person|Title|UserList,score:int,priority:int} $primaryCandidate */
        $primaryCandidate = $candidates->first();
        /** @var array{type:'list'|'person'|'title',record:Person|Title|UserList,score:int,priority:int}|null $secondaryCandidate */
        $secondaryCandidate = $candidates->get(1);

        return [
            'type' => $primaryCandidate['type'],
            'record' => $primaryCandidate['record'],
            'secondaryType' => $secondaryCandidate['type'] ?? null,
            'secondaryRecord' => $secondaryCandidate['record'] ?? null,
        ];
    }

    /**
     * @param  array<int, string|null>  $candidates
     */
    private function scoreMatchCandidate(string $searchQuery, array $candidates): int
    {
        $normalizedQuery = Str::of($searchQuery)->squish()->lower()->value();
        $score = 0;

        foreach ($candidates as $candidate) {
            $normalizedCandidate = Str::of((string) $candidate)->squish()->lower()->value();

            if ($normalizedCandidate === '') {
                continue;
            }

            $score = max($score, match (true) {
                $normalizedCandidate === $normalizedQuery => 400,
                Str::startsWith($normalizedCandidate, $normalizedQuery) => 280,
                Str::contains($normalizedCandidate, $normalizedQuery) => 200,
                default => 0,
            });
        }

        return $score;
    }

    /**
     * @return array<int, string>
     */
    private function splitAliases(?string $alternateNames): array
    {
        return array_values(array_filter(
            preg_split('/\s*\|\s*|\s*,\s*/', (string) $alternateNames) ?: [],
            static fn (string $alias): bool => $alias !== '',
        ));
    }
}
