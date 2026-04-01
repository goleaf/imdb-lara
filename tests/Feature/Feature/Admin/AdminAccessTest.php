<?php

namespace Tests\Feature\Feature\Admin;

use App\Models\User;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_routes_are_restricted_to_staff(): void
    {
        $member = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($member)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->seed(DemoCatalogSeeder::class);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Admin Dashboard');

        $this->actingAs($admin)
            ->get(route('admin.titles.index'))
            ->assertOk()
            ->assertSee('Manage Titles');

        $this->actingAs($admin)
            ->get(route('admin.reviews.index'))
            ->assertOk()
            ->assertSee('Moderate Reviews');
    }
}
