<?php

namespace Tests\Feature\Feature;

use App\Models\User;
use Livewire\Livewire;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_homepage_renders_the_catalog_home_sections_for_the_mysql_backed_surface(): void
    {
        Livewire::withoutLazyLoading();

        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('Catalog Spotlight')
            ->assertSee('Start anywhere')
            ->assertSee('Genre hubs')
            ->assertSee('Search and charts')
            ->assertSee('Awards Spotlight')
            ->assertSee('Latest Trailers')
            ->assertSeeHtml('data-slot="home-awards-spotlight"')
            ->assertSeeHtml('data-slot="home-trailers-preview"')
            ->assertSee('Trending titles')
            ->assertSee('Movies')
            ->assertSee('Series')
            ->assertSee('Popular people')
            ->assertSeeHtml('data-slot="person-card-metrics"')
            ->assertSee('Discovery')
            ->assertSee('Advanced Search');
    }

    public function test_homepage_navigation_reflects_the_catalog_only_route_set(): void
    {
        Livewire::withoutLazyLoading();

        $this->get(route('public.home'))
            ->assertOk()
            ->assertSeeInOrder(['Home', 'Discovery', 'All Titles', 'Movies', 'TV Shows', 'People', 'Awards', 'Charts', 'Trailers', 'Advanced Search'])
            ->assertDontSee('Lists')
            ->assertDontSee('Latest Reviews')
            ->assertDontSee('Sign In')
            ->assertDontSee('Create Account')
            ->assertDontSee('Watchlist');
    }

    public function test_homepage_does_not_render_legacy_shortcuts_even_for_signed_in_admins(): void
    {
        Livewire::withoutLazyLoading();

        $admin = User::factory()->admin()->make();

        $this->actingAs($admin)
            ->get(route('public.home'))
            ->assertOk()
            ->assertDontSee('Admin')
            ->assertDontSee('Watchlist')
            ->assertDontSee('Sign out')
            ->assertDontSee('Sign in')
            ->assertDontSee('Create account');
    }
}
