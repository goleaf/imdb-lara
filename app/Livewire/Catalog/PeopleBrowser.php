<?php

namespace App\Livewire\Catalog;

use App\Actions\Catalog\BuildPublicPeopleIndexQueryAction;
use App\Actions\Catalog\GetPublicPeopleFilterOptionsAction;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PeopleBrowser extends Component
{
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

    public function render()
    {
        $people = $this->buildPublicPeopleIndexQuery
            ->handle([
                'search' => $this->search,
                'profession' => $this->profession,
                'sort' => $this->sort,
            ])
            ->simplePaginate(18, pageName: 'people')
            ->withQueryString();

        $filterOptions = $this->getPublicPeopleFilterOptions->handle();
        $sortOptions = collect($filterOptions['sortOptions'])
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

        return view('livewire.catalog.people-browser', [
            'people' => $people,
            'professions' => $filterOptions['professions'],
            'sortOptions' => $sortOptions,
        ]);
    }
}
