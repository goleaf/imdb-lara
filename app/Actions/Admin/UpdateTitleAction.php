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
        $title->fill($attributes);
        $title->sort_title = filled($title->sort_title) ? $title->sort_title : $title->name;
        $title->save();

        return $title->refresh();
    }
}
