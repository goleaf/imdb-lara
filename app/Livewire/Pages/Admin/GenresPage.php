<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminGenresIndexQueryAction;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\Genre;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class GenresPage extends Component
{
    use RendersPageView;
    use WithPagination;

    public ?Genre $genre = null;

    public function mount(?Genre $genre = null): void
    {
        $this->genre = $genre;
    }

    public function render(BuildAdminGenresIndexQueryAction $buildAdminGenresIndexQuery): View
    {
        if (request()->routeIs('admin.genres.index')) {
            return $this->renderPageView('admin.genres.index', [
                'genres' => $buildAdminGenresIndexQuery
                    ->handle()
                    ->simplePaginate(20)
                    ->withQueryString(),
            ]);
        }

        if (request()->routeIs('admin.genres.create')) {
            return $this->renderPageView('admin.genres.create', [
                'genre' => new Genre,
            ]);
        }

        abort_unless($this->genre instanceof Genre, 404);

        if ($this->isCatalogOnlyApplication()) {
            return $this->renderPageView('admin.genres.edit', [
                'genre' => $this->genre,
            ]);
        }

        return $this->renderPageView('admin.genres.edit', [
            'genre' => $this->genre->loadCount('titles'),
        ]);
    }
}
