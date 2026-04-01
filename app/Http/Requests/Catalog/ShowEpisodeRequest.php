<?php

namespace App\Http\Requests\Catalog;

use App\Enums\TitleType;
use App\Http\Requests\NotFoundFormRequest;
use App\Models\Season;
use App\Models\Title;

class ShowEpisodeRequest extends NotFoundFormRequest
{
    public function authorize(): bool
    {
        $series = $this->route('series');
        $season = $this->route('season');
        $episode = $this->route('episode');

        if (! $series instanceof Title || ! $season instanceof Season || ! $episode instanceof Title) {
            return false;
        }

        $episode->loadMissing('episodeMeta');

        $canViewSeries = $series->is_published || ($this->user()?->can('view', $series) ?? false);
        $canViewEpisode = $episode->is_published || ($this->user()?->can('view', $episode) ?? false);

        return $episode->title_type === TitleType::Episode
            && $episode->episodeMeta !== null
            && $episode->episodeMeta->series_id === $series->id
            && $episode->episodeMeta->season_id === $season->id
            && $canViewSeries
            && $canViewEpisode;
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

    public function episode(): Title
    {
        /** @var Title */
        return $this->route('episode');
    }
}
