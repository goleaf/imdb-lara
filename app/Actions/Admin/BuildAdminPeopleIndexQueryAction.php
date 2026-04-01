<?php

namespace App\Actions\Admin;

use App\Models\Person;
use Illuminate\Database\Eloquent\Builder;

class BuildAdminPeopleIndexQueryAction
{
    public function handle(): Builder
    {
        return Person::query()
            ->select([
                'id',
                'name',
                'slug',
                'known_for_department',
                'nationality',
                'popularity_rank',
                'is_published',
            ])
            ->withCount(['credits', 'professions', 'mediaAssets'])
            ->orderBy('name');
    }
}
