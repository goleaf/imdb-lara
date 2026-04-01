<?php

namespace App\Actions\Admin;

use App\Actions\Admin\Concerns\NormalizesAdminAttributes;
use App\Models\Title;

class StoreTitleAction
{
    use NormalizesAdminAttributes;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): Title
    {
        $attributes = $this->normalizeAttributes($attributes);
        $genreIds = array_map('intval', $attributes['genre_ids'] ?? []);

        unset($attributes['genre_ids']);

        $attributes['is_published'] = (bool) ($attributes['is_published'] ?? false);
        $attributes['sort_title'] = $attributes['name'];

        $title = Title::query()->create($attributes);
        $title->genres()->sync($genreIds);

        return $title->refresh();
    }
}
