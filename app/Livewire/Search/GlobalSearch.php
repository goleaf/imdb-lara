<?php

namespace App\Livewire\Search;

use App\Actions\Search\BuildGlobalSearchViewDataAction;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $query = '';

    public string $searchRoute = '';

    /**
     * @var array<string, mixed>
     */
    public array $contextFilters = [];

    protected BuildGlobalSearchViewDataAction $buildGlobalSearchViewData;

    public function boot(
        BuildGlobalSearchViewDataAction $buildGlobalSearchViewData,
    ): void {
        $this->buildGlobalSearchViewData = $buildGlobalSearchViewData;
    }

    public function mount(): void
    {
        $this->query = trim((string) request('q'));
        $this->searchRoute = route('public.search');
        $this->contextFilters = request()->routeIs('public.search')
            ? collect(request()->only([
                'type',
                'genre',
                'theme',
                'yearFrom',
                'yearTo',
                'ratingMin',
                'ratingMax',
                'votesMin',
                'language',
                'country',
                'runtime',
                'status',
                'sort',
            ]))
                ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
                ->all()
            : [];
    }

    public function submitSearch(): void
    {
        $query = trim($this->query);

        if ($query === '') {
            $this->redirectRoute('public.search');

            return;
        }

        $this->redirectRoute('public.search', ['q' => $query]);
    }

    public function render()
    {
        return view('livewire.search.global-search', [
            'searchRoute' => $this->searchRoute,
            ...$this->buildGlobalSearchViewData->handle($this->query, titleFilters: $this->contextFilters),
        ]);
    }
}
