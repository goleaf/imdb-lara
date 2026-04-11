<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\DeleteCreditAction;
use App\Actions\Admin\SaveCreditAction;
use App\Http\Requests\Admin\StoreCreditRequest;
use App\Http\Requests\Admin\UpdateCreditRequest;
use App\Livewire\Pages\Admin\Concerns\ValidatesFormRequests;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\Credit;
use App\Models\Episode;
use App\Models\Person;
use App\Models\PersonProfession;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

class CreditsPage extends Component
{
    use RendersPageView;
    use ValidatesFormRequests;

    public ?Credit $credit = null;

    #[Url(as: 'title')]
    public ?int $title_id = null;

    #[Url(as: 'person')]
    public ?int $person_id = null;

    public ?int $person_profession_id = null;

    public ?int $episode_id = null;

    public string $department = '';

    public string $job = '';

    public ?string $character_name = null;

    public ?string $credited_as = null;

    public ?int $billing_order = null;

    public bool $is_principal = false;

    public function mount(?Credit $credit = null): void
    {
        $this->credit = $credit;
        $this->fillCreditForm($credit);
    }

    public function render(): View
    {
        $credit = $this->credit instanceof Credit
            ? $this->credit->load([
                'title:id,name,slug',
                'person:id,name,slug',
                'profession:id,person_id,department,profession,is_primary,sort_order',
                'episode.title:id,name,slug',
            ])->fill($this->creditPayload())
            : new Credit($this->creditPayload());

        return $this->renderPageView(
            $this->credit instanceof Credit ? 'admin.credits.edit' : 'admin.credits.create',
            [
                'credit' => $credit,
                ...$this->formOptions(),
            ],
        );
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
                    $this->person_id !== null,
                    fn ($query) => $query->where('person_id', $this->person_id),
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
                    $this->title_id !== null,
                    fn ($query) => $query->where(function ($episodeQuery): void {
                        $episodeQuery
                            ->where('series_id', $this->title_id)
                            ->orWhere('title_id', $this->title_id);
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

    public function updatedPersonId(): void
    {
        $this->person_profession_id = null;
    }

    public function updatedTitleId(): void
    {
        $this->episode_id = null;
    }

    public function saveCredit(SaveCreditAction $saveCredit): mixed
    {
        $validated = $this->credit instanceof Credit
            ? $this->validateWithFormRequest(UpdateCreditRequest::class, $this->creditPayload(), [
                'credit' => $this->credit,
            ])
            : $this->validateWithFormRequest(StoreCreditRequest::class, $this->creditPayload());

        $savedCredit = $saveCredit->handle($this->credit ?? new Credit, $validated);

        $this->credit = $savedCredit;
        $this->fillCreditForm($savedCredit);
        $this->resetValidation();
        session()->flash('status', $savedCredit->wasRecentlyCreated ? 'Credit created.' : 'Credit updated.');

        return $this->redirectRoute('admin.credits.edit', $savedCredit);
    }

    public function deleteCredit(DeleteCreditAction $deleteCredit): mixed
    {
        abort_unless($this->credit instanceof Credit, 404);

        $this->authorize('delete', $this->credit);
        $deleteCredit->handle($this->credit);
        session()->flash('status', 'Credit deleted.');

        return $this->redirectRoute('admin.dashboard');
    }

    private function fillCreditForm(?Credit $credit): void
    {
        $this->title_id = $credit?->title_id ?? $this->title_id;
        $this->person_id = $credit?->person_id ?? $this->person_id;
        $this->person_profession_id = $credit?->person_profession_id;
        $this->episode_id = $credit?->episode_id;
        $this->department = (string) ($credit?->department ?? '');
        $this->job = (string) ($credit?->job ?? '');
        $this->character_name = $credit?->character_name;
        $this->credited_as = $credit?->credited_as;
        $this->billing_order = $credit?->billing_order;
        $this->is_principal = (bool) ($credit?->is_principal ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    private function creditPayload(): array
    {
        return [
            'title_id' => $this->title_id,
            'person_id' => $this->person_id,
            'person_profession_id' => $this->person_profession_id,
            'episode_id' => $this->episode_id,
            'department' => $this->department,
            'job' => $this->job,
            'character_name' => $this->character_name,
            'credited_as' => $this->credited_as,
            'billing_order' => $this->billing_order,
            'is_principal' => $this->is_principal,
        ];
    }
}
