<?php

namespace App\Actions\Admin;

use App\Actions\Admin\Concerns\NormalizesAdminAttributes;
use App\Models\Title;

class UpdateTitleAction
{
    use NormalizesAdminAttributes;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Title $title, array $attributes): Title
    {
        $shouldSyncGenres = array_key_exists('genre_ids', $attributes);
        $attributes = $this->normalizeAttributes($attributes);
        $genreIds = array_map('intval', $attributes['genre_ids'] ?? []);

        unset($attributes['genre_ids']);

        $attributes['is_published'] = (bool) ($attributes['is_published'] ?? false);
        $attributes['sort_title'] = $attributes['name'];

        $title->fill($attributes);
        $title->save();

        if ($shouldSyncGenres) {
            $title->genres()->sync($genreIds);
        }

        return $title->refresh();
    }
}
