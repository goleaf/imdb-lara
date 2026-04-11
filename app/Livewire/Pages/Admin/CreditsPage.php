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
        if (! ($this->credit instanceof Credit)) {
            $credit = new Credit([
                'title_id' => $this->selectedTitleId(),
                'person_id' => $this->selectedPersonId(),
            ]);

            return $this->renderPageView('admin.credits.create', [
                'credit' => $credit,
                ...$this->formOptions(),
            ]);
        }

        return $this->renderPageView('admin.credits.edit', [
            'credit' => $this->credit->load([
                'title:id,name,slug',
                'person:id,name,slug',
                'profession:id,person_id,department,profession,is_primary,sort_order',
                'episode.title:id,name,slug',
            ]),
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
        $selectedTitleId = $this->selectedTitleId();
        $selectedPersonId = $this->selectedPersonId();

        return [
            'titles' => Title::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->limit(500)
                ->get(),
            'people' => Person::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->limit(500)
                ->get(),
            'professions' => PersonProfession::query()
                ->select([
                    'id',
                    'person_id',
                    'department',
                    'profession',
                    'is_primary',
                    'sort_order',
                ])
                ->when(
                    $selectedPersonId !== null,
                    fn ($query) => $query->where('person_id', $selectedPersonId),
                )
                ->with('person:id,name')
                ->orderBy('person_id')
                ->orderByDesc('is_primary')
                ->orderBy('sort_order')
                ->orderBy('profession')
                ->limit(250)
                ->get(),
            'episodes' => Episode::query()
                ->select([
                    'id',
                    'title_id',
                    'series_id',
                    'season_id',
                    'season_number',
                    'episode_number',
                    'absolute_number',
                    'production_code',
                    'aired_at',
                ])
                ->when(
                    $selectedTitleId !== null,
                    fn ($query) => $query->where(function ($episodeQuery) use ($selectedTitleId): void {
                        $episodeQuery
                            ->where('series_id', $selectedTitleId)
                            ->orWhere('title_id', $selectedTitleId);
                    }),
                )
                ->with('title:id,name')
                ->orderBy('season_number')
                ->orderBy('episode_number')
                ->orderBy('id')
                ->limit(250)
                ->get(),
        ];
    }

    private function selectedPersonId(): ?int
    {
        $selectedPersonId = old('person_id', $this->credit?->person_id ?? request()->query('person'));

        return is_numeric($selectedPersonId) ? (int) $selectedPersonId : null;
    }

    private function selectedTitleId(): ?int
    {
        $selectedTitleId = old('title_id', $this->credit?->title_id ?? request()->query('title'));

        return is_numeric($selectedTitleId) ? (int) $selectedTitleId : null;
    }
}
