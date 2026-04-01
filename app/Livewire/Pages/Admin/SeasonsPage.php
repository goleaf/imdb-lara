<?php

namespace App\Livewire\Pages\Admin;

use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\Season;
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

        return $this->renderPageView('admin.seasons.edit', [
            'season' => $this->season->load([
                'series:id,name,slug',
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
        ]);
    }
}
