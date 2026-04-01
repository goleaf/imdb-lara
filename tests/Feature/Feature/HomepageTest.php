<?php

namespace Tests\Feature\Feature;

use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_all_imdb_style_sections_with_seeded_content(): void
    {
        $this->seed(DemoCatalogSeeder::class);
        Livewire::withoutLazyLoading();

        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('Hero Spotlight')
            ->assertSee('Trending Now')
            ->assertSee('Top Rated Movies')
            ->assertSee('Top Rated TV Shows')
            ->assertSee('Coming Soon')
            ->assertSee('Recently Added Titles')
            ->assertSee('Popular People')
            ->assertSee('Latest Trailers')
            ->assertSee('Latest Reviews')
            ->assertSee('Featured Public Lists')
            ->assertSee('Browse by Genre')
            ->assertSee('Browse by Year')
            ->assertSee('Northern Signal')
            ->assertSee('Static Bloom')
            ->assertSee('Aurora Run')
            ->assertSee('Ava Mercer')
            ->assertSee('Weekend Marathon')
            ->assertSee('2026');
    }

    public function test_homepage_renders_clean_empty_states_without_catalog_data(): void
    {
        Livewire::withoutLazyLoading();

        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('Hero Spotlight')
            ->assertSee('No trending titles are available yet.')
            ->assertSee('No public people profiles are available yet.')
            ->assertSee('No public trailers are available yet.')
            ->assertSee('No public lists are featured right now.')
            ->assertSee('No genres are ready to browse yet.')
            ->assertSee('No release years are available yet.');
    }
}
