<?php

namespace App\Http\Requests\Catalog;

use App\Enums\TitleType;
use App\Http\Requests\NotFoundFormRequest;
use App\Models\Season;
use App\Models\Title;

class ShowSeasonRequest extends NotFoundFormRequest
{
    public function authorize(): bool
    {
        $series = $this->route('series');
        $season = $this->route('season');

        return $series instanceof Title
            && $season instanceof Season
            && in_array($series->title_type, [TitleType::Series, TitleType::MiniSeries], true)
            && $season->series_id === $series->id
            && ($series->is_published || ($this->user()?->can('view', $series) ?? false));
    }

    public function series(): Title
    {
        /** @var Title */
        return $this->route('series');
    }

    public function season(): Season
    {
        /** @var Season */
        return $this->route('season');
    }
}
