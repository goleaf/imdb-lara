<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\SaveGenreAction;
use App\Http\Controllers\Admin\Concerns\BlocksCatalogOnlyAdminMutations;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGenreRequest;
use App\Http\Requests\Admin\UpdateGenreRequest;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;

class GenreController extends Controller
{
    use BlocksCatalogOnlyAdminMutations;

    public function store(StoreGenreRequest $request, SaveGenreAction $saveGenre): RedirectResponse
    {
        $this->abortIfCatalogOnly();

        $genre = $saveGenre->handle(new Genre, $request->validated());

        return redirect()->route('admin.genres.edit', $genre)->with('status', 'Genre created.');
    }

    public function update(
        UpdateGenreRequest $request,
        Genre $genre,
        SaveGenreAction $saveGenre,
    ): RedirectResponse {
        $this->abortIfCatalogOnly();

        $genre = $saveGenre->handle($genre, $request->validated());

        return redirect()->route('admin.genres.edit', $genre)->with('status', 'Genre updated.');
    }

    public function destroy(Genre $genre): RedirectResponse
    {
        $this->abortIfCatalogOnly();
        $this->authorize('delete', $genre);

        $genre->delete();

        return redirect()->route('admin.genres.index')->with('status', 'Genre deleted.');
    }
}
