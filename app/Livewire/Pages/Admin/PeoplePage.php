<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminPeopleIndexQueryAction;
use App\Livewire\Pages\Concerns\RendersLegacyPage;
use App\Models\Person;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class PeoplePage extends Component
{
    use RendersLegacyPage;
    use WithPagination;

    public ?Person $person = null;

    public function mount(?Person $person = null): void
    {
        $this->person = $person;
    }

    public function render(BuildAdminPeopleIndexQueryAction $buildAdminPeopleIndexQuery): View
    {
        if (request()->routeIs('admin.people.index')) {
            return $this->renderLegacyPage('admin.people.index', [
                'people' => $buildAdminPeopleIndexQuery
                    ->handle()
                    ->simplePaginate(20)
                    ->withQueryString(),
            ]);
        }

        if (request()->routeIs('admin.people.create')) {
            return $this->renderLegacyPage('admin.people.create', [
                'person' => new Person,
            ]);
        }

        abort_unless($this->person instanceof Person, 404);

        return $this->renderLegacyPage('admin.people.edit', [
            'person' => $this->person->load([
                'professions' => fn ($professionQuery) => $professionQuery->select([
                    'id',
                    'person_id',
                    'department',
                    'profession',
                    'is_primary',
                    'sort_order',
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
                    ->with([
                        'title:id,name,slug',
                        'episode.title:id,name',
                        'profession:id,profession',
                    ]),
                'mediaAssets' => fn ($mediaQuery) => $mediaQuery->select([
                    'id',
                    'mediable_type',
                    'mediable_id',
                    'kind',
                    'url',
                    'alt_text',
                    'caption',
                    'is_primary',
                    'position',
                    'published_at',
                ]),
            ]),
        ]);
    }
}
