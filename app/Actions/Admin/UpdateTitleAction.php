<?php

namespace App\Actions\Admin;

use App\Models\Title;

class UpdateTitleAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Title $title, array $attributes): Title
    {
        $attributes = collect($attributes)
            ->map(fn (mixed $value): mixed => $value === '' ? null : $value)
            ->all();
        $attributes['is_published'] = (bool) ($attributes['is_published'] ?? false);

        $title->fill($attributes);
        $title->sort_title = $title->name;
        $title->save();

        return $title->refresh();
    }
}
