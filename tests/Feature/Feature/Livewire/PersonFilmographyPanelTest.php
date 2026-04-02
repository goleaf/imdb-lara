<?php

namespace Tests\Feature\Feature\Livewire;

use App\Livewire\People\FilmographyPanel;
use App\Models\Credit;
use App\Models\Person;
use App\Models\PersonProfession;
use App\Models\Title;
use App\Models\TitleStatistic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PersonFilmographyPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_filmography_panel_groups_titles_by_profession(): void
    {
        $person = Person::factory()->create();
        $actorProfession = PersonProfession::factory()->for($person)->primary()->create([
            'profession' => 'Actor',
            'department' => 'Cast',
        ]);
        $writerProfession = PersonProfession::factory()->for($person)->create([
            'profession' => 'Writer',
            'department' => 'Writing',
            'sort_order' => 1,
        ]);

        $movie = Title::factory()->create([
            'name' => 'Northern Signal',
            'release_year' => 2024,
        ]);
        $series = Title::factory()->create([
            'name' => 'Static Bloom',
            'release_year' => 2021,
        ]);

        Credit::factory()->for($person)->for($movie)->create([
            'department' => 'Cast',
            'job' => 'Actor',
            'character_name' => 'Dr. Mara Elling',
            'person_profession_id' => $actorProfession->id,
        ]);
        Credit::factory()->for($person)->for($series)->create([
            'department' => 'Writing',
            'job' => 'Writer',
            'person_profession_id' => $writerProfession->id,
        ]);

        Livewire::test(FilmographyPanel::class, ['person' => $person])
            ->assertSee('Filmography')
            ->assertSee('Actor')
            ->assertSee('Writer')
            ->assertSee('Northern Signal')
            ->assertSee('Static Bloom')
            ->assertSee(route('public.titles.show', $movie), false)
            ->assertSee(route('public.titles.show', $series), false);
    }

    public function test_filmography_panel_filters_and_sorts_titles(): void
    {
        $person = Person::factory()->create();
        $actorProfession = PersonProfession::factory()->for($person)->primary()->create([
            'profession' => 'Actor',
            'department' => 'Cast',
        ]);

        $olderTitle = Title::factory()->create([
            'name' => 'Harbor Nine',
            'release_year' => 2021,
        ]);
        $newerTitle = Title::factory()->create([
            'name' => 'Aurora Run',
            'release_year' => 2026,
        ]);

        TitleStatistic::factory()->for($olderTitle)->create([
            'average_rating' => 9.1,
            'rating_count' => 120,
        ]);
        TitleStatistic::factory()->for($newerTitle)->create([
            'average_rating' => 7.2,
            'rating_count' => 40,
        ]);

        Credit::factory()->for($person)->for($olderTitle)->create([
            'department' => 'Cast',
            'job' => 'Actor',
            'person_profession_id' => $actorProfession->id,
        ]);
        Credit::factory()->for($person)->for($newerTitle)->create([
            'department' => 'Cast',
            'job' => 'Actor',
            'person_profession_id' => $actorProfession->id,
        ]);

        Livewire::test(FilmographyPanel::class, ['person' => $person])
            ->set('profession', 'Actor')
            ->assertSee('Harbor Nine')
            ->assertSee('Aurora Run')
            ->set('sort', 'rating')
            ->assertSeeInOrder(['Harbor Nine', 'Aurora Run']);
    }
}
