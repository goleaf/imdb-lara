<?php

namespace App\Livewire\Catalog;

use App\Actions\Catalog\BuildPublicPeopleIndexQueryAction;
use App\Actions\Catalog\GetPublicPeopleFilterOptionsAction;
use App\Livewire\Catalog\Concerns\HandlesRemoteCatalogFailures;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PeopleBrowser extends Component
{
    use HandlesRemoteCatalogFailures;
    use WithPagination;

    protected BuildPublicPeopleIndexQueryAction $buildPublicPeopleIndexQuery;

    protected GetPublicPeopleFilterOptionsAction $getPublicPeopleFilterOptions;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public ?string $profession = null;

    #[Url]
    public string $sort = 'popular';

    public function boot(
        BuildPublicPeopleIndexQueryAction $buildPublicPeopleIndexQuery,
        GetPublicPeopleFilterOptionsAction $getPublicPeopleFilterOptions,
    ): void {
        $this->buildPublicPeopleIndexQuery = $buildPublicPeopleIndexQuery;
        $this->getPublicPeopleFilterOptions = $getPublicPeopleFilterOptions;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedProfession(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function viewData(): array
    {
        return $this->resolveRemoteCatalogViewData(
            resolver: function (): array {
                $people = $this->buildPublicPeopleIndexQuery
                    ->handle([
                        'search' => $this->search,
                        'profession' => $this->profession,
                        'sort' => $this->sort,
                    ])
                    ->simplePaginate(18, pageName: 'people')
                    ->withQueryString();

                $filterOptions = $this->getPublicPeopleFilterOptions->handle();

                return [
                    'emptyHeading' => 'No people match the current filters.',
                    'emptyText' => 'Adjust the keyword or profession filter to widen the directory.',
                    'isCatalogUnavailable' => false,
                    'people' => $people,
                    'professions' => $filterOptions['professions'],
                    'sortOptions' => $this->formatSortOptions($filterOptions['sortOptions']),
                    'statusHeading' => '',
                    'statusText' => '',
                ];
            },
            fallback: fn (): array => [
                'people' => $this->emptyPaginator(18, 'people'),
                'professions' => [],
                'sortOptions' => $this->formatSortOptions([
                    ['value' => 'popular', 'label' => 'Popular'],
                    ['value' => 'credits', 'label' => 'Credits'],
                    ['value' => 'awards', 'label' => 'Awards'],
                    ['value' => 'name', 'label' => 'Name'],
                ]),
                ...$this->unavailableCatalogState('people'),
            ],
        );
    }

    /**
     * @param  iterable<array-key, array{label: string, value: string}>  $sortOptions
     * @return list<array{icon: string, label: string, value: string}>
     */
    private function formatSortOptions(iterable $sortOptions): array
    {
        return collect($sortOptions)
            ->map(fn (array $option): array => [
                ...$option,
                'icon' => match ($option['value']) {
                    'popular' => 'fire',
                    'credits' => 'film',
                    'awards' => 'trophy',
                    default => 'bars-arrow-down',
                },
            ])
            ->all();
    }

    public function render(): View
    {
        return view('livewire.catalog.people-browser');
    }

    public function paginationSimpleView(): string
    {
        return 'livewire.pagination.island-simple';
    }

    public function paginationIslandName(): string
    {
        return 'people-browser-page';
    }
}
