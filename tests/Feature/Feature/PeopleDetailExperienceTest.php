<?php

namespace Tests\Feature\Feature;

use App\Models\Person;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeopleDetailExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_person_page_renders_biography_known_for_filmography_and_collaborators(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $person = Person::query()->where('slug', 'ava-mercer')->firstOrFail();

        $this->get(route('public.people.show', $person))
            ->assertOk()
            ->assertSeeHtml('data-slot="avatar"')
            ->assertSeeHtml('data-slot="accordion"')
            ->assertSee($person->name)
            ->assertSee('A. Mercer')
            ->assertSee('Biography')
            ->assertSee('Known for')
            ->assertSee('Filmography')
            ->assertSee('Awards')
            ->assertSee('Frequent collaborators')
            ->assertSee('Related titles')
            ->assertSee('Northern Signal')
            ->assertSee('Harbor Nine: The Deep End');
    }

    public function test_people_browse_page_renders_the_livewire_directory_surface(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $this->get(route('public.people.index'))
            ->assertOk()
            ->assertSeeHtml('data-slot="avatar"')
            ->assertSee('Browse People')
            ->assertSee('Actors')
            ->assertSee('Ava Mercer')
            ->assertSee('Talia Rowe');
    }
}
