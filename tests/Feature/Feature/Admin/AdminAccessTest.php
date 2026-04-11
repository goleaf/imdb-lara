<?php

namespace Tests\Feature\Feature\Admin;

use App\Models\Title;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_routes_follow_the_role_access_matrix(): void
    {
        $title = Title::factory()->create();
        $regularUser = User::factory()->create();
        $contributor = User::factory()->contributor()->create();
        $editor = User::factory()->editor()->create();
        $moderator = User::factory()->moderator()->create();
        $admin = User::factory()->admin()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($regularUser)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($contributor)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Admin Dashboard');

        $this->actingAs($editor)
            ->get(route('admin.titles.index'))
            ->assertOk()
            ->assertSee('Manage Titles');

        $this->actingAs($editor)
            ->get(route('admin.titles.edit', $title))
            ->assertOk()
            ->assertSee('Edit '.$title->name);

        $this->actingAs($editor)
            ->get(route('admin.reviews.index'))
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Admin Dashboard');

        $this->actingAs($moderator)
            ->get(route('admin.titles.index'))
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get(route('admin.titles.edit', $title))
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get(route('admin.reviews.index'))
            ->assertOk()
            ->assertSee('Moderate Reviews');

        $this->actingAs($moderator)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Reports');

        $this->actingAs($admin)
            ->get(route('admin.titles.index'))
            ->assertOk()
            ->assertSee('Manage Titles');

        $this->actingAs($admin)
            ->get(route('admin.titles.edit', $title))
            ->assertOk()
            ->assertSee('Edit '.$title->name);

        $this->actingAs($admin)
            ->get(route('admin.reviews.index'))
            ->assertOk()
            ->assertSee('Moderate Reviews');

        $this->actingAs($superAdmin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Reports');
    }

    public function test_admin_navigation_entry_is_only_visible_to_staff_roles(): void
    {
        $regularUser = User::factory()->create();
        $contributor = User::factory()->contributor()->create();
        $editor = User::factory()->editor()->create();

        $this->actingAs($regularUser)
            ->get(route('public.discover'))
            ->assertOk()
            ->assertDontSee('Admin');

        $this->actingAs($contributor)
            ->get(route('account.watchlist'))
            ->assertOk()
            ->assertDontSee('Admin');

        $this->actingAs($editor)
            ->get(route('public.discover'))
            ->assertOk()
            ->assertSee('Admin');

        $this->actingAs($editor)
            ->get(route('account.watchlist'))
            ->assertOk()
            ->assertSee('Admin');
    }

    public function test_admin_pages_render_the_portal_sidebar_shell(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeHtml('data-theme-enabled="true"')
            ->assertSeeHtml('data-slot="layout"')
            ->assertSee('Catalog control')
            ->assertSee('Admin area');
    }

    public function test_admin_title_edit_flash_message_uses_alert_description_markup(): void
    {
        $title = Title::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->withSession(['status' => 'Title updated.'])
            ->get(route('admin.titles.edit', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="alert-description"');
    }

    public function test_suspended_staff_cannot_access_admin_routes_or_see_the_admin_shortcut(): void
    {
        $title = Title::factory()->create();
        $editor = User::factory()->editor()->suspended()->create();
        $moderator = User::factory()->moderator()->suspended()->create();
        $superAdmin = User::factory()->superAdmin()->suspended()->create();

        $this->actingAs($editor)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get(route('admin.reviews.index'))
            ->assertForbidden();

        $this->actingAs($superAdmin)
            ->get(route('admin.titles.edit', $title))
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(route('public.discover'))
            ->assertOk()
            ->assertDontSee('Admin');
    }
}
