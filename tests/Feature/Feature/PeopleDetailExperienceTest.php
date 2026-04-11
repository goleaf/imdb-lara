<?php

namespace Tests\Feature\Feature;

use App\Models\NameBasicAlternativeName;
use App\Models\Person;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class PeopleDetailExperienceTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_person_page_renders_the_current_catalog_profile_surface(): void
    {
        Livewire::withoutLazyLoading();

        $person = $this->samplePerson();

        $this->get(route('public.people.show', $person))
            ->assertOk()
            ->assertSeeHtml('data-slot="people-detail-hero"')
            ->assertSeeHtml('data-slot="people-detail-career-profile"')
            ->assertSeeHtml('data-slot="person-filmography-panel"')
            ->assertSeeHtml('data-slot="avatar"')
            ->assertSee($person->name)
            ->assertSee('Catalog profile')
            ->assertSee('Career profile')
            ->assertSee('Known for')
            ->assertSee('Filmography')
            ->assertSee('Awards summary')
            ->assertSee('Frequent collaborators')
            ->assertSee('Related titles');
    }

    public function test_person_page_uses_catalog_only_copy_without_account_or_review_actions(): void
    {
        Livewire::withoutLazyLoading();

        $person = $this->samplePerson();

        $this->get(route('public.people.show', $person))
            ->assertOk()
            ->assertDontSee('Write a review')
            ->assertDontSee('Your rating')
            ->assertDontSee('Watchlist')
            ->assertDontSee('Create account');
    }

    public function test_people_browse_page_renders_the_livewire_directory_surface(): void
    {
        Livewire::withoutLazyLoading();

        $this->get(route('public.people.index'))
            ->assertOk()
            ->assertSeeHtml('data-slot="browse-people-hero"')
            ->assertSee('Browse People')
            ->assertSee('Actors')
            ->assertSee('Directors')
            ->assertSee('Writers');
    }

    public function test_person_page_surfaces_raw_alternative_name_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $alternativeNameRow = NameBasicAlternativeName::query()
            ->select(['name_basic_id', 'alternative_name', 'position'])
            ->whereNotNull('alternative_name')
            ->orderBy('name_basic_id')
            ->orderBy('position')
            ->first();

        if (! $alternativeNameRow instanceof NameBasicAlternativeName) {
            $this->markTestSkipped('The remote catalog does not currently expose name_basic_alternative_names rows.');
        }

        $person = Person::query()
            ->select($this->remotePersonColumns())
            ->published()
            ->whereKey($alternativeNameRow->name_basic_id)
            ->first();

        if (! $person instanceof Person) {
            $this->markTestSkipped('The selected alternative-name row is not linked to a published person profile.');
        }

        $response = $this->get(route('public.people.show', $person))
            ->assertOk()
            ->assertSeeHtml('data-slot="people-detail-alternative-names"')
            ->assertSee('name_basic_id')
            ->assertSee('alternative_name')
            ->assertSee('position')
            ->assertSee((string) $alternativeNameRow->name_basic_id)
            ->assertSee($alternativeNameRow->alternative_name);

        if (is_int($alternativeNameRow->position)) {
            $response->assertSee((string) $alternativeNameRow->position);
        }
    }
}
