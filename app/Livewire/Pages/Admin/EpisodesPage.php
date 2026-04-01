<?php

namespace App\Livewire\Pages\Admin;

use App\Livewire\Pages\Concerns\RendersLegacyPage;
use App\Models\Episode;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class EpisodesPage extends Component
{
    use RendersLegacyPage;

    public ?Episode $episode = null;

    public function mount(?Episode $episode = null): void
    {
        $this->episode = $episode;
    }

    public function render(): View
    {
        abort_unless($this->episode instanceof Episode, 404);

        return $this->renderLegacyPage('admin.episodes.edit', [
            'episode' => $this->episode->load([
                'title',
                'season.series',
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
