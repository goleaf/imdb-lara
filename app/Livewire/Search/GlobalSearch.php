<?php

namespace App\Livewire\Search;

use App\Actions\Search\BuildGlobalSearchViewDataAction;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

class GlobalSearch extends Component
{
    #[Url(as: 'q')]
    public string $query = '';

    #[Locked]
    public string $searchRoute = '';

    /**
     * @var array<string, mixed>
     */
    #[Locked]
    public array $contextFilters = [];

    protected BuildGlobalSearchViewDataAction $buildGlobalSearchViewData;

    public function boot(
        BuildGlobalSearchViewDataAction $buildGlobalSearchViewData,
    ): void {
        $this->buildGlobalSearchViewData = $buildGlobalSearchViewData;
    }

    public function mount(): void
    {
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

    #[Computed]
    public function viewData(): array
    {
        return [
            'searchRoute' => $this->searchRoute,
            ...$this->buildGlobalSearchViewData->handle($this->query, titleFilters: $this->contextFilters),
        ];
    }

    public function render(): View
    {
        return view('livewire.search.global-search', $this->viewData);
    }
}
