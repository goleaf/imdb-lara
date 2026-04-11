<?php

namespace App\Livewire\Search;

use App\Actions\Search\BuildGlobalSearchViewDataAction;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $query = '';

    public string $searchRoute = '';

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
            ...$this->buildGlobalSearchViewData->handle($this->query),
        ]);
    }
}
