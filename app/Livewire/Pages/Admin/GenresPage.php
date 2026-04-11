<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminGenresIndexQueryAction;
use App\Actions\Admin\SaveGenreAction;
use App\Http\Requests\Admin\StoreGenreRequest;
use App\Http\Requests\Admin\UpdateGenreRequest;
use App\Livewire\Pages\Admin\Concerns\ValidatesFormRequests;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\Genre;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class GenresPage extends Component
{
    use RendersPageView;
    use ValidatesFormRequests;

    public ?Genre $genre = null;

    public string $name = '';

    public string $slug = '';

    public ?string $description = null;

    public function mount(?Genre $genre = null): void
    {
        $this->genre = $genre;
        $this->fillGenreForm($genre ?? new Genre);
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
            'genre' => new Genre($this->genrePayload()),
        ]);
    }

    protected function renderGenreEditPage(): View
    {
        abort_unless($this->genre instanceof Genre, 404);

        if ($this->isCatalogOnlyApplication()) {
            return $this->renderPageView('admin.genres.edit', [
                'genre' => $this->genre->fill($this->genrePayload()),
            ]);
        }

        return $this->renderPageView('admin.genres.edit', [
            'genre' => $this->genre->loadCount('titles')->fill($this->genrePayload()),
        ]);
    }

    public function saveGenre(SaveGenreAction $saveGenre): mixed
    {
        $validated = $this->genre instanceof Genre
            ? $this->validateWithFormRequest(UpdateGenreRequest::class, $this->genrePayload(), [
                'genre' => $this->genre,
            ])
            : $this->validateWithFormRequest(StoreGenreRequest::class, $this->genrePayload());

        $savedGenre = $saveGenre->handle($this->genre ?? new Genre, $validated);

        $this->genre = $savedGenre;
        $this->fillGenreForm($savedGenre);
        $this->resetValidation();
        session()->flash('status', $savedGenre->wasRecentlyCreated ? 'Genre created.' : 'Genre updated.');

        return $this->redirectRoute('admin.genres.edit', $savedGenre);
    }

    public function deleteGenre(): mixed
    {
        abort_unless($this->genre instanceof Genre, 404);

        $this->authorize('delete', $this->genre);
        $this->genre->delete();
        session()->flash('status', 'Genre deleted.');

        return $this->redirectRoute('admin.genres.index');
    }

    private function fillGenreForm(Genre $genre): void
    {
        $this->name = (string) $genre->name;
        $this->slug = (string) $genre->slug;
        $this->description = $genre->description;
    }

    /**
     * @return array<string, mixed>
     */
    private function genrePayload(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
        ];
    }
}
