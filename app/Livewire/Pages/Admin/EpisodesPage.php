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

        return $this->renderPageView('admin.episodes.edit', [
            'episode' => $this->episode->load([
                'title' => fn ($titleQuery) => $titleQuery->select(Title::catalogCardColumns()),
                'season:id,series_id,name,slug,season_number',
                'series:id,name,slug,title_type,is_published',
            ]),
        ]);
    }
}
