<?php

namespace App\Livewire\Search;

use App\Actions\Search\BuildDiscoveryViewDataAction;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class DiscoveryFilters extends Component
{
    use WithPagination;

    protected BuildDiscoveryViewDataAction $buildDiscoveryViewData;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public ?string $genre = null;

    #[Url]
    public ?string $theme = null;

    #[Url]
    public ?string $type = null;

    #[Url]
    public string $sort = 'popular';

    #[Url]
    public ?string $minimumRating = null;

    #[Url]
    public ?string $yearFrom = null;

    #[Url]
    public ?string $yearTo = null;

    #[Url]
    public ?string $votesMin = null;

    #[Url]
    public ?string $language = null;

    #[Url]
    public ?string $country = null;

    #[Url]
    public ?string $runtime = null;

    #[Url]
    public ?string $awards = null;

    public function boot(
        BuildDiscoveryViewDataAction $buildDiscoveryViewData,
    ): void {
        $this->buildDiscoveryViewData = $buildDiscoveryViewData;
    }

    public function updated(string $property): void
    {
        if (! str_starts_with($property, 'paginators.')) {
            $this->resetPage(pageName: 'discover');
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->genre = null;
        $this->theme = null;
        $this->type = null;
        $this->sort = 'popular';
        $this->minimumRating = null;
        $this->yearFrom = null;
        $this->yearTo = null;
        $this->votesMin = null;
        $this->language = null;
        $this->country = null;
        $this->runtime = null;
        $this->awards = null;
        $this->resetPage(pageName: 'discover');
    }

    #[Computed]
    public function viewData(): array
    {
        return $this->buildDiscoveryViewData->handle([
            'search' => $this->search,
            'genre' => $this->genre,
            'theme' => $this->theme,
            'type' => $this->type,
            'sort' => $this->sort,
            'minimumRating' => $this->minimumRating,
            'yearFrom' => $this->yearFrom,
            'yearTo' => $this->yearTo,
            'votesMin' => $this->votesMin,
            'language' => $this->language,
            'country' => $this->country,
            'runtime' => $this->runtime,
            'awards' => $this->awards,
        ], pageName: 'discover');
    }

    public function render(): View
    {
        return view('livewire.search.discovery-filters');
    }

    public function paginationSimpleView(): string
    {
        return 'livewire.pagination.island-simple';
    }

    public function paginationIslandName(): string
    {
        return 'discover-results-page';
    }
}
