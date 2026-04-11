<?php

namespace Tests\Feature\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class PortalSurfaceSmokeTest extends TestCase
{
    use RefreshDatabase;
    use UsesCatalogOnlyApplication;

    public function test_auth_routes_render_the_livewire_auth_shell(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sign in to Screenbase');

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Create your Screenbase account');
    }

    public function test_account_routes_are_available_once_authenticated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard');

        $this->actingAs($user)
            ->get(route('account.watchlist'))
            ->assertOk()
            ->assertSee('Your Watchlist');
    }

    public function test_public_livewire_portal_pages_render_without_the_legacy_catalog_gate(): void
    {
        $user = User::factory()->create([
            'name' => 'Dana Viewer',
            'username' => 'dana-viewer',
        ]);

        $this->get(route('public.lists.index'))
            ->assertOk()
            ->assertSee('Browse Public Lists');

        $this->get(route('public.reviews.latest'))
            ->assertOk()
            ->assertSee('Latest Reviews');

        $this->get(route('public.users.show', $user))
            ->assertOk()
            ->assertSee('Dana Viewer');
    }
}
