<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminPeopleIndexQueryAction;
use App\Actions\Admin\DeletePersonAction;
use App\Actions\Admin\SaveMediaAssetAction;
use App\Actions\Admin\SavePersonAction;
use App\Enums\MediaKind;
use App\Http\Requests\Admin\StoreMediaAssetRequest;
use App\Http\Requests\Admin\StorePersonRequest;
use App\Http\Requests\Admin\UpdatePersonRequest;
use App\Livewire\Pages\Admin\Concerns\ValidatesFormRequests;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\MediaAsset;
use App\Models\Person;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class PeoplePage extends Component
{
    use RendersPageView;
    use ValidatesFormRequests;
    use WithFileUploads;

    public ?Person $person = null;

    public string $name = '';

    public string $slug = '';

    public ?string $alternate_names = null;

    public ?string $known_for_department = null;

    public ?int $popularity_rank = null;

    public ?string $birth_date = null;

    public ?string $death_date = null;

    public ?string $birth_place = null;

    public ?string $death_place = null;

    public ?string $nationality = null;

    public bool $is_published = true;

    public ?string $short_biography = null;

    public ?string $biography = null;

    public ?string $meta_title = null;

    public ?string $search_keywords = null;

    public ?string $meta_description = null;

    /**
     * @var array<string, mixed>
     */
    public array $draftMediaAsset = [];

    public function mount(?Person $person = null): void
    {
        $this->person = $person;
        $this->fillPersonForm($person ?? new Person(['is_published' => true]));
        $this->initializeDraftMediaAsset();
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
            'person' => new Person($this->personPayload()),
        ]);
    }

    protected function renderPersonEditPage(): View
    {
        abort_unless($this->person instanceof Person, 404);

        $loadedPerson = $this->person->load([
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
        ]);
        $loadedPerson->fill($this->personPayload());

        if ($this->isCatalogOnlyApplication()) {
            return $this->renderPageView('admin.people.edit', [
                'person' => $loadedPerson,
            ]);
        }

        return $this->renderPageView('admin.people.edit', [
            'person' => $loadedPerson,
            'defaultProfessionSortOrder' => (($loadedPerson->getRelation('professions')?->max('sort_order') ?? 0) + 1),
            'draftMediaAsset' => tap(
                new MediaAsset($this->draftMediaAssetPayload()),
                fn (MediaAsset $mediaAsset) => $mediaAsset->setRelation('mediable', $loadedPerson),
            ),
        ]);
    }

    #[On('person-professions-updated')]
    public function refreshPersonProfessions(): void
    {
        if ($this->person instanceof Person) {
            $this->person->refresh();
        }
    }

    public function savePerson(SavePersonAction $savePerson): mixed
    {
        $validated = $this->person instanceof Person
            ? $this->validateWithFormRequest(UpdatePersonRequest::class, $this->personPayload(), [
                'person' => $this->person,
            ])
            : $this->validateWithFormRequest(StorePersonRequest::class, $this->personPayload());

        $savedPerson = $savePerson->handle($this->person ?? new Person, $validated);

        $this->person = $savedPerson;
        $this->fillPersonForm($savedPerson);
        $this->initializeDraftMediaAsset();
        $this->resetValidation();
        session()->flash('status', $savedPerson->wasRecentlyCreated ? 'Person created.' : 'Person updated.');

        return $this->redirectRoute('admin.people.edit', $savedPerson);
    }

    public function saveDraftMediaAsset(SaveMediaAssetAction $saveMediaAsset): void
    {
        abort_unless($this->person instanceof Person, 404);

        $validated = $this->validateWithFormRequest(
            StoreMediaAssetRequest::class,
            $this->draftMediaAssetPayload(),
            ['person' => $this->person],
        );

        $saveMediaAsset->handle(
            new MediaAsset,
            $this->person,
            $validated,
        );

        $this->initializeDraftMediaAsset();
        $this->person->refresh();
        $this->resetValidation();
        session()->flash('status', 'Media asset added.');
    }

    public function deletePerson(DeletePersonAction $deletePerson): mixed
    {
        abort_unless($this->person instanceof Person, 404);

        $this->authorize('delete', $this->person);
        $deletePerson->handle($this->person);
        session()->flash('status', 'Person deleted.');

        return $this->redirectRoute('admin.people.index');
    }

    private function fillPersonForm(Person $person): void
    {
        $this->name = (string) $person->name;
        $this->slug = (string) $person->slug;
        $this->alternate_names = $person->alternate_names;
        $this->known_for_department = $person->known_for_department;
        $this->popularity_rank = $person->popularity_rank;
        $this->birth_date = $person->birth_date?->toDateString();
        $this->death_date = $person->death_date?->toDateString();
        $this->birth_place = $person->birth_place;
        $this->death_place = $person->death_place;
        $this->nationality = $person->nationality;
        $this->is_published = (bool) $person->is_published;
        $this->short_biography = $person->short_biography;
        $this->biography = $person->biography;
        $this->meta_title = $person->meta_title;
        $this->search_keywords = $person->search_keywords;
        $this->meta_description = $person->meta_description;
    }

    private function initializeDraftMediaAsset(): void
    {
        $this->draftMediaAsset = [
            'kind' => MediaKind::Headshot->value,
            'file' => null,
            'url' => null,
            'alt_text' => null,
            'caption' => null,
            'width' => null,
            'height' => null,
            'provider' => null,
            'provider_key' => null,
            'language' => null,
            'duration_seconds' => null,
            'metadata' => null,
            'is_primary' => true,
            'position' => 0,
            'published_at' => null,
            'mediable_type' => Person::class,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function personPayload(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'alternate_names' => $this->alternate_names,
            'known_for_department' => $this->known_for_department,
            'popularity_rank' => $this->popularity_rank,
            'birth_date' => $this->birth_date,
            'death_date' => $this->death_date,
            'birth_place' => $this->birth_place,
            'death_place' => $this->death_place,
            'nationality' => $this->nationality,
            'is_published' => $this->is_published,
            'short_biography' => $this->short_biography,
            'biography' => $this->biography,
            'meta_title' => $this->meta_title,
            'search_keywords' => $this->search_keywords,
            'meta_description' => $this->meta_description,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function draftMediaAssetPayload(): array
    {
        return $this->draftMediaAsset;
    }
}
