<?php

namespace App\Actions\Admin;

use App\Models\Title;
use Illuminate\Database\Eloquent\Builder;

class BuildAdminTitlesIndexQueryAction
{
    public function handle(): Builder
    {
        return Title::query()
            ->select(['id', 'name', 'slug', 'title_type', 'release_year', 'is_published'])
            ->orderBy('name');
    }
}
