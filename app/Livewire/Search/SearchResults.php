<?php

namespace App\Livewire\Search;

use App\Actions\Search\BuildSearchResultsViewDataAction;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SearchResults extends Component
{
    use WithPagination;

    protected BuildSearchResultsViewDataAction $buildSearchResultsViewData;

    #[Url(as: 'q')]
    public string $query = '';

    #[Url]
    public ?string $type = null;

    #[Url]
    public ?string $genre = null;

    #[Url]
    public ?string $theme = null;

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
        BuildSearchResultsViewDataAction $buildSearchResultsViewData,
    ): void {
        $this->buildSearchResultsViewData = $buildSearchResultsViewData;
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
        $this->theme = null;
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

    #[Computed]
    public function viewData(): array
    {
        return $this->buildSearchResultsViewData->handle([
            'country' => $this->country,
            'genre' => $this->genre,
            'language' => $this->language,
            'query' => $this->query,
            'ratingMax' => $this->ratingMax,
            'ratingMin' => $this->ratingMin,
            'runtime' => $this->runtime,
            'sort' => $this->sort,
            'status' => $this->status,
            'theme' => $this->theme,
            'type' => $this->type,
            'votesMin' => $this->votesMin,
            'yearFrom' => $this->yearFrom,
            'yearTo' => $this->yearTo,
        ]);
    }

    public function render(): View
    {
        return view('livewire.search.search-results');
    }

    public function paginationSimpleView(): string
    {
        return 'livewire.pagination.island-simple';
    }

    public function paginationIslandName(): string
    {
        return 'search-results-page';
    }
}
