<?php

namespace App\Livewire\Search;

use App\Actions\Search\GetGlobalSearchSuggestionsAction;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $query = '';

    protected GetGlobalSearchSuggestionsAction $getGlobalSearchSuggestions;

    public function boot(GetGlobalSearchSuggestionsAction $getGlobalSearchSuggestions): void
    {
        $this->getGlobalSearchSuggestions = $getGlobalSearchSuggestions;
    }

    public function mount(): void
    {
        $this->query = trim((string) request('q'));
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

        return view('livewire.search.global-search', [
            'hasSearchTerm' => mb_strlen($query) >= 2,
            'suggestions' => $this->getGlobalSearchSuggestions->handle($query),
        ]);
    }
}
