<?php

namespace Tests\Feature\Feature\Livewire;

use App\Livewire\Catalog\PeopleBrowser;
use App\Models\Person;
use App\Models\PersonProfession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PeopleBrowserTest extends TestCase
{
    use RefreshDatabase;

    public function test_people_browser_uses_combobox_filters(): void
    {
        PersonProfession::factory()->create([
            'profession' => 'Actor',
        ]);

        Livewire::test(PeopleBrowser::class)
            ->assertSeeHtml('data-slot="combobox-input"')
            ->assertDontSeeHtml('<select');
    }

    public function test_people_browser_filters_by_search_and_profession(): void
    {
        $actor = Person::factory()->create([
            'name' => 'Ava Mercer',
            'alternate_names' => 'A. Mercer',
            'imdb_alternative_names' => ['Ava L. Mercer'],
            'slug' => 'ava-mercer',
        ]);
        $director = Person::factory()->create([
            'name' => 'Talia Rowe',
        ]);

        PersonProfession::factory()->for($actor)->primary()->create([
            'profession' => 'Actor',
            'department' => 'Cast',
        ]);
        PersonProfession::factory()->for($director)->primary()->create([
            'profession' => 'Director',
            'department' => 'Directing',
        ]);

        Livewire::test(PeopleBrowser::class)
            ->set('search', 'Ava')
            ->assertSee('Ava Mercer')
            ->assertDontSee('Talia Rowe')
            ->set('search', 'Ava L. Mercer')
            ->assertSee('Ava Mercer')
            ->assertDontSee('Talia Rowe')
            ->set('search', 'ava-mercer')
            ->assertSee('Ava Mercer')
            ->assertDontSee('Talia Rowe')
            ->set('search', '')
            ->set('profession', 'Director')
            ->assertSee('Talia Rowe')
            ->assertDontSee('Ava Mercer')
            ->assertSee(route('public.people.show', $director), false);
    }
}
