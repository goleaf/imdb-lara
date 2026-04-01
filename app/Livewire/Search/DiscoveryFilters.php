<?php

namespace App\Livewire\Search;

use App\Actions\Search\BuildDiscoveryQueryAction;
use App\Enums\TitleType;
use App\Models\Genre;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class DiscoveryFilters extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $genre = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $sort = 'popular';

    #[Url]
    public string $minimumRating = '';

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
        $titles = app(BuildDiscoveryQueryAction::class)
            ->handle([
                'search' => $this->search,
                'genre' => $this->genre,
                'type' => $this->type,
                'sort' => $this->sort,
                'minimumRating' => $this->minimumRating,
            ])
            ->simplePaginate(12, pageName: 'discover')
            ->withQueryString();

        return view('livewire.search.discovery-filters', [
            'titles' => $titles,
            'genres' => Genre::query()->select(['id', 'name', 'slug'])->orderBy('name')->get(),
            'titleTypes' => TitleType::cases(),
        ]);
    }
}
