<?php

namespace App\Actions\Admin;

use App\Actions\Admin\Concerns\NormalizesAdminAttributes;
use App\Models\Season;
use App\Models\Title;

class SaveSeasonAction
{
    use NormalizesAdminAttributes;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Season $season, Title $series, array $attributes): Season
    {
        $attributes = $this->normalizeAttributes($attributes);
        $attributes['series_id'] = $series->id;

        $season->fill($attributes);
        $season->save();

        return $season->refresh();
    }
}
