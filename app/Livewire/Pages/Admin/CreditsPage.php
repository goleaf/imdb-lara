<?php

namespace App\Livewire\Pages\Admin;

use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\Credit;
use App\Models\Episode;
use App\Models\Person;
use App\Models\PersonProfession;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class CreditsPage extends Component
{
    use RendersPageView;

    public ?Credit $credit = null;

    public function mount(?Credit $credit = null): void
    {
        $this->credit = $credit;
    }

    public function render(): View
    {
        if (request()->routeIs('admin.credits.create')) {
            return $this->renderPageView('admin.credits.create', [
                'credit' => new Credit([
                    'title_id' => request()->integer('title'),
                    'person_id' => request()->integer('person'),
                ]),
                ...$this->formOptions(),
            ]);
        }

        abort_unless($this->credit instanceof Credit, 404);

        return $this->renderPageView('admin.credits.edit', [
            'credit' => $this->credit->load(['title', 'person', 'profession', 'episode.title']),
            ...$this->formOptions(),
        ]);
    }

    /**
     * @return array{
     *     titles: Collection<int, Title>,
     *     people: Collection<int, Person>,
     *     professions: Collection<int, PersonProfession>,
     *     episodes: Collection<int, Episode>
     * }
     */
    private function formOptions(): array
    {
        $selectedTitleId = $this->credit?->title_id ?? request()->integer('title');
        $selectedPersonId = $this->credit?->person_id ?? request()->integer('person');
        $selectedProfessionId = $this->credit?->person_profession_id;
        $selectedEpisodeId = $this->credit?->episode_id;

        return [
            'titles' => Title::query()
                ->select(['id', 'name', 'slug', 'title_type'])
                ->orderBy('name')
                ->limit(250)
                ->get(),
            'people' => Person::query()
                ->select(['id', 'name', 'slug'])
                ->orderBy('name')
                ->limit(250)
                ->get(),
            'professions' => PersonProfession::query()
                ->select(['id', 'person_id', 'department', 'profession'])
                ->when($selectedPersonId, function ($professionQuery) use ($selectedPersonId, $selectedProfessionId): void {
                    $professionQuery->where(function ($scopedQuery) use ($selectedPersonId, $selectedProfessionId): void {
                        $scopedQuery->where('person_id', $selectedPersonId);

                        if ($selectedProfessionId !== null) {
                            $scopedQuery->orWhere('id', $selectedProfessionId);
                        }
                    });
                })
                ->with('person:id,name')
                ->orderBy('person_id')
                ->orderBy('department')
                ->orderBy('profession')
                ->limit(250)
                ->get(),
            'episodes' => Episode::query()
                ->select(['id', 'title_id', 'season_id', 'episode_number'])
                ->when($selectedTitleId, function ($episodeQuery) use ($selectedTitleId, $selectedEpisodeId): void {
                    $episodeQuery->where(function ($scopedQuery) use ($selectedTitleId, $selectedEpisodeId): void {
                        $scopedQuery
                            ->where('series_id', $selectedTitleId)
                            ->orWhere('title_id', $selectedTitleId);

                        if ($selectedEpisodeId !== null) {
                            $scopedQuery->orWhere('id', $selectedEpisodeId);
                        }
                    });
                })
                ->with(['title:id,name', 'season:id,name'])
                ->orderBy('season_id')
                ->orderBy('episode_number')
                ->orderBy('id')
                ->limit(250)
                ->get(),
        ];
    }
}
