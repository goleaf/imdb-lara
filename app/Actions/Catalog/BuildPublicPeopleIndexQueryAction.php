<?php

namespace App\Actions\Catalog;

use App\Enums\MediaKind;
use App\Models\Person;
use Illuminate\Database\Eloquent\Builder;

class BuildPublicPeopleIndexQueryAction
{
    public function handle(): Builder
    {
        return Person::query()
            ->select(['id', 'name', 'slug', 'known_for_department', 'biography', 'popularity_rank', 'is_published'])
            ->published()
            ->withCount('credits')
            ->with([
                'mediaAssets' => fn ($query) => $query
                    ->select(['id', 'mediable_type', 'mediable_id', 'kind', 'url', 'alt_text', 'position', 'is_primary'])
                    ->where('kind', MediaKind::Headshot)
                    ->orderBy('position')
                    ->limit(1),
            ])
            ->orderBy('name');
    }
}
