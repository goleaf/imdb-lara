<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\LoadEpisodeDetailsAction;
use App\Enums\TitleType;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\Season;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class EpisodeShowPage extends Component
{
    use RendersPageView;

    public ?Title $episode = null;

    public ?Season $season = null;

    public ?Title $series = null;

    public function mount(Title $series, Season $season, Title $episode): void
    {
        $episode->loadMissing('episodeMeta');

        $canViewSeries = $series->is_published || (auth()->user()?->can('view', $series) ?? false);
        $canViewEpisode = $episode->is_published || (auth()->user()?->can('view', $episode) ?? false);

        abort_unless(
            $episode->title_type === TitleType::Episode
            && $episode->episodeMeta !== null
            && $episode->episodeMeta->series_id === $series->id
            && $episode->episodeMeta->season_id === $season->id
            && $canViewSeries
            && $canViewEpisode,
            404,
        );

        $this->series = $series;
        $this->season = $season;
        $this->episode = $episode;
    }

    public function render(LoadEpisodeDetailsAction $loadEpisodeDetails): View
    {
        abort_unless(
            $this->series instanceof Title
            && $this->season instanceof Season
            && $this->episode instanceof Title,
            404,
        );

        return $this->renderPageView('episodes.show', $loadEpisodeDetails->handle(
            $this->series,
            $this->season,
            $this->episode,
        ));
    }
}
