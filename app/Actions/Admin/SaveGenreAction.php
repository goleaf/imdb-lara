<?php

namespace App\Actions\Admin;

use App\Actions\Admin\Concerns\NormalizesAdminAttributes;
use App\Models\Genre;

class SaveGenreAction
{
    use NormalizesAdminAttributes;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Genre $genre, array $attributes): Genre
    {
        $genre->fill($this->normalizeAttributes($attributes));
        $genre->save();

        return $genre->refresh();
    }
}
