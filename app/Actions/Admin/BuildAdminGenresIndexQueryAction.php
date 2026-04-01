<?php

namespace App\Actions\Admin;

use App\Models\Genre;
use Illuminate\Database\Eloquent\Builder;

class BuildAdminGenresIndexQueryAction
{
    public function handle(): Builder
    {
        return Genre::query()
            ->select(['id', 'name', 'slug', 'description'])
            ->withCount('titles')
            ->orderBy('name');
    }
}
