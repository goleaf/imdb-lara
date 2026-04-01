<?php

namespace App\Actions\Catalog;

use App\Models\Credit;
use App\Models\Title;
use Illuminate\Database\Eloquent\Builder;

class BuildTitleCreditsQueryAction
{
    public function handle(Title $title): Builder
    {
        return Credit::query()
            ->select([
                'id',
                'title_id',
                'person_id',
                'department',
                'job',
                'character_name',
                'billing_order',
                'is_principal',
                'episode_id',
                'credited_as',
            ])
            ->whereBelongsTo($title)
            ->with([
                'person:id,name,slug,known_for_department',
                'episode:id,title_id,series_id,season_id,season_number,episode_number',
                'episode.title:id,name,slug',
                'episode.season:id,series_id,slug',
                'episode.series:id,slug',
            ])
            ->orderBy('department')
            ->orderBy('billing_order')
            ->orderBy('job')
            ->orderBy('id');
    }
}
