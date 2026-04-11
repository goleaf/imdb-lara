<?php

namespace App\Livewire\Pages\Admin;

use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\Episode;
use App\Models\Season;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SeasonsPage extends Component
{
    use RendersPageView;

    public ?Season $season = null;

    public function mount(?Season $season = null): void
    {
        $this->season = $season;
    }

    public function render(): View
    {
        abort_unless($this->season instanceof Season, 404);

        if ($this->isCatalogOnlyApplication()) {
            return $this->renderPageView('admin.seasons.edit', [
                'season' => $this->season->load([
                    'series' => fn ($seriesQuery) => $seriesQuery->select(Title::catalogCardColumns()),
                ]),
            ]);
        }

        return $this->renderPageView('admin.seasons.edit', [
            'season' => $this->season->load([
                'series' => fn ($seriesQuery) => $seriesQuery->select(Title::catalogCardColumns()),
                'episodes' => fn ($episodeQuery) => $episodeQuery
                    ->select([
                        'id',
                        'title_id',
                        'season_id',
                        'season_number',
                        'episode_number',
                        'absolute_number',
                        'production_code',
                        'aired_at',
                    ])
                    ->with('title:id,name,slug,is_published'),
            ]),
            'draftEpisode' => tap(
                new Episode([
                    'season_number' => $this->season->season_number,
                    'episode_number' => (($this->season->episodes->max('episode_number') ?? 0) + 1),
                ]),
                function (Episode $episode): void {
                    $episode->setRelation('title', new Title([
                        'is_published' => true,
                        'release_year' => $this->season->release_year,
                    ]));
                },
            ),
        ]);
    }
}
