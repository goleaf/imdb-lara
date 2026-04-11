<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminPeopleIndexQueryAction;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\MediaAsset;
use App\Models\Person;
use App\Models\PersonProfession;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PeoplePage extends Component
{
    use RendersPageView;

    public ?Person $person = null;

    public function mount(?Person $person = null): void
    {
        $this->person = $person;
    }

    protected function renderPeopleIndexPage(BuildAdminPeopleIndexQueryAction $buildAdminPeopleIndexQuery): View
    {
        return $this->renderPageView('admin.people.index', [
            'people' => $buildAdminPeopleIndexQuery
                ->handle()
                ->simplePaginate(20)
                ->withQueryString(),
        ]);
    }

    protected function renderPersonCreatePage(): View
    {
        return $this->renderPageView('admin.people.create', [
            'person' => new Person,
        ]);
    }

    protected function renderPersonEditPage(): View
    {
        abort_unless($this->person instanceof Person, 404);

        if ($this->isCatalogOnlyApplication()) {
            return $this->renderPageView('admin.people.edit', [
                'person' => $this->person,
            ]);
        }

        return $this->renderPageView('admin.people.edit', [
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
            'draftProfession' => new PersonProfession([
                'sort_order' => (($this->person->professions->max('sort_order') ?? 0) + 1),
            ]),
            'draftMediaAsset' => new MediaAsset([
                'mediable_type' => Person::class,
                'kind' => \App\Enums\MediaKind::Headshot,
                'is_primary' => true,
            ]),
        ]);
    }
}
