<?php

namespace App\Livewire\Catalog;

use App\Actions\Catalog\BuildPublicInterestCategoryIndexQueryAction;
use App\Livewire\Catalog\Concerns\HandlesRemoteCatalogFailures;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class InterestCategoryBrowser extends Component
{
    use HandlesRemoteCatalogFailures;
    use WithPagination;

    protected BuildPublicInterestCategoryIndexQueryAction $buildPublicInterestCategoryIndexQuery;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $sort = 'popular';

    public bool $showAll = false;

    public bool $showImages = false;

    public function boot(BuildPublicInterestCategoryIndexQueryAction $buildPublicInterestCategoryIndexQuery): void
    {
        $this->buildPublicInterestCategoryIndexQuery = $buildPublicInterestCategoryIndexQuery;
    }

    public function updatedSearch(): void
    {
        $this->resetPage(pageName: 'interest-categories');
    }

    public function updatedSort(): void
    {
        $this->resetPage(pageName: 'interest-categories');
    }

    #[Computed]
    public function viewData(): array
    {
        return $this->resolveRemoteCatalogViewData(
            resolver: fn (): array => $this->resolvedViewData(),
            fallback: fn (): array => [
                'hasPagination' => ! $this->showAll,
                'interestCategories' => $this->showAll
                    ? new EloquentCollection
                    : $this->emptyPaginator(18, 'interest-categories'),
                'showImages' => $this->showImages,
                'sortOptions' => $this->sortOptions(),
                ...$this->unavailableCatalogState('themes'),
            ],
        );
    }

    private function resolvedViewData(): array
    {
        $interestCategoriesQuery = $this->buildPublicInterestCategoryIndexQuery
            ->handle([
                'search' => $this->search,
                'showImages' => $this->showImages,
                'sort' => $this->sort,
            ]);

        $interestCategories = $this->showAll
            ? $interestCategoriesQuery->get()
            : $interestCategoriesQuery->simplePaginate(18, pageName: 'interest-categories')->withQueryString();

        return [
            'emptyHeading' => 'No interest categories match the current filters.',
            'emptyText' => 'Adjust the keyword or sort mode to widen the current catalog lane.',
            'hasPagination' => ! $this->showAll,
            'interestCategories' => $interestCategories,
            'isCatalogUnavailable' => false,
            'showImages' => $this->showImages,
            'sortOptions' => $this->sortOptions(),
            'statusHeading' => '',
            'statusText' => '',
        ];
    }

    /**
     * @return list<array{icon: string, label: string, value: string}>
     */
    private function sortOptions(): array
    {
        return [
            ['value' => 'popular', 'label' => 'Most linked', 'icon' => 'sparkles'],
            ['value' => 'interests', 'label' => 'Most interests', 'icon' => 'squares-2x2'],
            ['value' => 'subgenres', 'label' => 'Most subgenres', 'icon' => 'tag'],
            ['value' => 'name', 'label' => 'Name', 'icon' => 'bars-arrow-down'],
        ];
    }

    public function render(): View
    {
        return view('livewire.catalog.interest-category-browser');
    }

    public function paginationSimpleView(): string
    {
        return 'livewire.pagination.island-simple';
    }

    public function paginationIslandName(): string
    {
        return 'interest-category-browser-page';
    }
}
