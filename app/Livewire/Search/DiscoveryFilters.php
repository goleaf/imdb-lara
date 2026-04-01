<?php

namespace App\Livewire\Search;

use App\Actions\Search\BuildDiscoveryQueryAction;
use App\Actions\Search\GetDiscoveryFilterOptionsAction;
use App\Actions\Search\GetDiscoveryTitleSuggestionsAction;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class DiscoveryFilters extends Component
{
    use WithPagination;

    protected BuildDiscoveryQueryAction $buildDiscoveryQuery;

    protected GetDiscoveryFilterOptionsAction $getDiscoveryFilterOptions;

    protected GetDiscoveryTitleSuggestionsAction $getDiscoveryTitleSuggestions;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public ?string $genre = null;

    #[Url]
    public ?string $type = null;

    #[Url]
    public string $sort = 'popular';

    #[Url]
    public ?string $minimumRating = null;

    public function boot(
        BuildDiscoveryQueryAction $buildDiscoveryQuery,
        GetDiscoveryFilterOptionsAction $getDiscoveryFilterOptions,
        GetDiscoveryTitleSuggestionsAction $getDiscoveryTitleSuggestions,
    ): void {
        $this->buildDiscoveryQuery = $buildDiscoveryQuery;
        $this->getDiscoveryFilterOptions = $getDiscoveryFilterOptions;
        $this->getDiscoveryTitleSuggestions = $getDiscoveryTitleSuggestions;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedGenre(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function updatedMinimumRating(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $searchSuggestions = $this->getDiscoveryTitleSuggestions->handle($this->search);

        $titles = $this->buildDiscoveryQuery
            ->handle([
                'search' => $this->search,
                'genre' => $this->genre,
                'type' => $this->type,
                'sort' => $this->sort,
                'minimumRating' => $this->minimumRating,
            ])
            ->simplePaginate(12, pageName: 'discover')
            ->withQueryString();

        $filterOptions = $this->getDiscoveryFilterOptions->handle();

        return view('livewire.search.discovery-filters', [
            'titles' => $titles,
            'genres' => $filterOptions['genres'],
            'titleTypes' => $filterOptions['titleTypes'],
            'minimumRatings' => $filterOptions['minimumRatings'],
            'sortOptions' => $filterOptions['sortOptions'],
            'searchSuggestions' => $searchSuggestions,
        ]);
    }
}
