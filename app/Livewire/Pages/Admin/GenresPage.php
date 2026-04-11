<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminGenresIndexQueryAction;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\Genre;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class GenresPage extends Component
{
    use RendersPageView;

    public ?Genre $genre = null;

    public function mount(?Genre $genre = null): void
    {
        $this->genre = $genre;
    }

    protected function renderGenresIndexPage(BuildAdminGenresIndexQueryAction $buildAdminGenresIndexQuery): View
    {
        return $this->renderPageView('admin.genres.index', [
            'genres' => $buildAdminGenresIndexQuery
                ->handle()
                ->simplePaginate(20)
                ->withQueryString(),
        ]);
    }

    protected function renderGenreCreatePage(): View
    {
        return $this->renderPageView('admin.genres.create', [
            'genre' => new Genre,
        ]);
    }

    protected function renderGenreEditPage(): View
    {
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
