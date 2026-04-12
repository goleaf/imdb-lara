<?php

namespace App\Actions\Admin;

use App\Actions\Admin\Concerns\NormalizesAdminAttributes;
use App\Actions\Admin\Concerns\ResolvesLocalCatalogWriteModels;
use App\Models\Genre;
use App\Models\LocalGenre;

class SaveGenreAction
{
    use NormalizesAdminAttributes;
    use ResolvesLocalCatalogWriteModels;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Genre $genre, array $attributes): LocalGenre
    {
        $genre = $genre->exists ? $this->resolveLocalGenre($genre) : new LocalGenre;
        $genre->fill($this->normalizeAttributes($attributes));
        $genre->save();

        return $genre->refresh();
    }
}
