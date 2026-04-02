<?php

namespace App\Livewire\Search;

use App\Actions\Search\GetGlobalSearchSuggestionsAction;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $query = '';

    public string $searchRoute = '';

    protected GetGlobalSearchSuggestionsAction $getGlobalSearchSuggestions;

    public function boot(GetGlobalSearchSuggestionsAction $getGlobalSearchSuggestions): void
    {
        $this->getGlobalSearchSuggestions = $getGlobalSearchSuggestions;
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
        $query = trim($this->query);
        $suggestions = $this->getGlobalSearchSuggestions->handle($query);

        return view('livewire.search.global-search', [
            'hasSuggestions' => $suggestions['titles']->isNotEmpty()
                || $suggestions['people']->isNotEmpty()
                || $suggestions['lists']->isNotEmpty(),
            'hasSearchTerm' => mb_strlen($query) >= 2,
            'searchRoute' => $this->searchRoute,
            'suggestions' => $suggestions,
            'trimmedQuery' => $query,
        ]);
    }
}
