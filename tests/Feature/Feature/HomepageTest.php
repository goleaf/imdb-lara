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
            ->assertSeeHtml('data-slot="home-hero"')
            ->assertDontSeeHtml('bg-[linear-gradient(140deg,rgba(15,23,42,0.98),rgba(23,23,23,0.96))]')
            ->assertSeeHtml('data-slot="site-footer"')
            ->assertSee('Screenbase')
            ->assertSee('Trending Now')
            ->assertSee('Top Rated Movies')
            ->assertSee('Top Rated TV Shows')
            ->assertSee('Coming Soon')
            ->assertSee('Recently Added Titles')
            ->assertSee('Popular People')
            ->assertSee('Featured talent')
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
            ->assertSee('2026')
            ->assertSeeHtml('data-slot="link-icon:after"');
    }

    public function test_homepage_renders_clean_empty_states_without_catalog_data(): void
    {
        Livewire::withoutLazyLoading();

        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('Hero Spotlight')
            ->assertSee('No trending titles are available yet.')
            ->assertSee('No rated movies are available yet.')
            ->assertSee('No rated TV shows are available yet.')
            ->assertSee('No upcoming releases are scheduled yet.')
            ->assertSee('No recently added titles are available yet.')
            ->assertSee('No public people profiles are available yet.')
            ->assertSee('No public trailers are available yet.')
            ->assertSee('No published reviews are available yet.')
            ->assertSee('No public lists are featured right now.')
            ->assertSee('No genres are ready to browse yet.')
            ->assertSee('No release years are available yet.')
            ->assertSeeHtml('data-slot="empty-media"');
    }
}
