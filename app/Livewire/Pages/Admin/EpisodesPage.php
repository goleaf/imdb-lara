<?php

namespace App\Livewire\Pages\Admin;

use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\Episode;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class EpisodesPage extends Component
{
    use RendersPageView;

    public ?Episode $episode = null;

    public function mount(?Episode $episode = null): void
    {
        $this->episode = $episode;
    }

    public function render(): View
    {
        abort_unless($this->episode instanceof Episode, 404);

        if ($this->isCatalogOnlyApplication()) {
            return $this->renderPageView('admin.episodes.edit', [
                'episode' => $this->episode->load([
                    'title' => fn ($titleQuery) => $titleQuery->select(Title::catalogCardColumns()),
                    'seasonRecord' => fn ($seasonQuery) => $seasonQuery
                        ->select([
                            'movie_seasons.movie_id',
                            'movie_seasons.season',
                            'movie_seasons.episode_count',
                        ])
                        ->with([
                            'series' => fn ($seriesQuery) => $seriesQuery->select(Title::catalogCardColumns()),
                        ]),
                ]),
            ]);
        }

        return $this->renderPageView('admin.episodes.edit', [
            'episode' => $this->episode->load([
                'title' => fn ($titleQuery) => $titleQuery->select(Title::catalogCardColumns()),
                'seasonRecord' => fn ($seasonQuery) => $seasonQuery
                    ->select([
                        'movie_seasons.movie_id',
                        'movie_seasons.season',
                        'movie_seasons.episode_count',
                    ])
                    ->with([
                        'series' => fn ($seriesQuery) => $seriesQuery->select(Title::catalogCardColumns()),
                    ]),
                'credits' => fn ($creditQuery) => $creditQuery
                    ->select([
                        'id',
                        'title_id',
                        'person_id',
                        'department',
                        'job',
                        'character_name',
                        'billing_order',
                        'credited_as',
                        'is_principal',
                        'person_profession_id',
                        'episode_id',
                    ])
                    ->with(['person:id,name,slug', 'profession:id,profession']),
            ]),
        ]);
    }
}
