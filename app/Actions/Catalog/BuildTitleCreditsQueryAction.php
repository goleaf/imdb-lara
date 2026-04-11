<?php

namespace App\Actions\Catalog;

use App\Models\Credit;
use App\Models\Title;
use Illuminate\Database\Eloquent\Builder;

class BuildTitleCreditsQueryAction
{
    public function handle(Title $title): Builder
    {
        $query = Credit::query()
            ->select(Credit::projectedColumns())
            ->with(Credit::projectedRelations())
            ->whereBelongsTo($title)
            ->with([
                'person:id,name,slug,known_for_department',
                'episode:id,title_id,series_id,season_id,season_number,episode_number',
                'episode.title:id,name,slug',
                'episode.season:id,series_id,slug',
                'episode.series:id,slug',
            ]);

        if (Credit::usesCatalogOnlySchema()) {
            return $query
                ->orderBy(Credit::qualifiedColumn('category'))
                ->orderBy(Credit::qualifiedColumn('billing_order'))
                ->orderBy('name_credits.id');
        }

        return $query
            ->orderBy('department')
            ->orderBy('billing_order')
            ->orderBy('job')
            ->orderBy('id');
    }
}
