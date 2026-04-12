<?php

namespace App\Actions\Admin;

use App\Actions\Admin\Concerns\NormalizesAdminAttributes;
use App\Actions\Admin\Concerns\ResolvesLocalCatalogWriteModels;
use App\Models\Season;
use App\Models\Title;

class SaveSeasonAction
{
    use NormalizesAdminAttributes;
    use ResolvesLocalCatalogWriteModels;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Season $season, Title $series, array $attributes): Season
    {
        $attributes = $this->normalizeAttributes($attributes);
        $attributes['series_id'] = $this->resolveLocalTitle($series)->id;

        $season->fill($attributes);
        $season->save();

        return $season->refresh();
    }
}
