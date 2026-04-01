<?php

namespace App\Livewire\Search;

use App\Actions\Catalog\BuildPublicPeopleIndexQueryAction;
use App\Actions\Search\BuildSearchPublicListsQueryAction;
use App\Actions\Search\BuildSearchTitleResultsQueryAction;
use App\Actions\Search\GetSearchFilterOptionsAction;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SearchResults extends Component
{
    use WithPagination;

    protected BuildPublicPeopleIndexQueryAction $buildPublicPeopleIndexQuery;

    protected BuildSearchPublicListsQueryAction $buildSearchPublicListsQuery;

    protected BuildSearchTitleResultsQueryAction $buildSearchTitleResultsQuery;

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
        BuildSearchPublicListsQueryAction $buildSearchPublicListsQuery,
        BuildSearchTitleResultsQueryAction $buildSearchTitleResultsQuery,
        GetSearchFilterOptionsAction $getSearchFilterOptions,
    ): void {
        $this->buildPublicPeopleIndexQuery = $buildPublicPeopleIndexQuery;
        $this->buildSearchPublicListsQuery = $buildSearchPublicListsQuery;
        $this->buildSearchTitleResultsQuery = $buildSearchTitleResultsQuery;
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
        $titles = $titleResultsQuery
            ->simplePaginate(12, pageName: 'titles')
            ->withQueryString();

        $people = collect();
        $peopleCount = 0;
        $lists = collect();
        $listsCount = 0;

        if ($showGroupedMatches) {
            $peopleResultsQuery = $this->buildPublicPeopleIndexQuery->handle([
                'search' => $searchQuery,
                'sort' => 'popular',
            ]);
            $peopleCount = (clone $peopleResultsQuery)->count();
            $people = $peopleResultsQuery->limit(6)->get();

            $listsResultsQuery = $this->buildSearchPublicListsQuery->handle($searchQuery);
            $listsCount = (clone $listsResultsQuery)->count();
            $lists = $listsResultsQuery->limit(6)->get();
        }

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
            'filterOptions' => $this->getSearchFilterOptions->handle(),
            'hasAnyResults' => $titleResultsCount > 0 || $peopleCount > 0 || $listsCount > 0,
            'lists' => $lists,
            'listsCount' => $listsCount,
            'people' => $people,
            'peopleCount' => $peopleCount,
            'showGroupedMatches' => $showGroupedMatches,
            'titleResultsCount' => $titleResultsCount,
            'titles' => $titles,
        ]);
    }
}
