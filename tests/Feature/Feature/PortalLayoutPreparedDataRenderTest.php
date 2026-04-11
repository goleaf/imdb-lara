<?php

namespace Tests\Feature\Feature;

use App\Actions\Seo\ResolvePageShellViewDataAction;
use App\Models\User;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class PortalLayoutPreparedDataRenderTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_livewire_layout_precomputes_portal_shell_flags_for_layout_blades(): void
    {
        $portalShell = app(ResolvePageShellViewDataAction::class)->forLivewireLayout([
            'shellVariant' => 'account',
            'navbar' => '<nav>Account shortcuts</nav>',
        ]);
        $defaultShell = app(ResolvePageShellViewDataAction::class)->forLivewireLayout([
            'shellVariant' => 'default',
        ]);

        $this->assertTrue($portalShell['isPortalShell']);
        $this->assertTrue($portalShell['hasRenderedNavbar']);
        $this->assertFalse($portalShell['isAuthShell']);
        $this->assertFalse($defaultShell['isPortalShell']);
        $this->assertFalse($defaultShell['hasRenderedNavbar']);
    }

    public function test_portal_partials_render_with_prepared_navigation_and_user_data(): void
    {
        $user = User::factory()->make([
            'name' => 'Dana Viewer',
            'username' => 'dana-viewer',
        ]);

        $this->actingAs($user);

        $accountNavbar = view('layouts.partials.account-navbar')->render();
        $accountSidebar = view('layouts.partials.account-sidebar')->render();
        $adminNavbar = view('layouts.partials.admin-navbar')->render();
        $adminSidebar = view('layouts.partials.admin-sidebar')->render();

        $this->assertStringContainsString('Discover', $accountNavbar);
        $this->assertStringContainsString('Dana Viewer', $accountSidebar);
        $this->assertStringContainsString('@dana-viewer', $accountSidebar);
        $this->assertStringContainsString('Dashboard', $adminNavbar);
        $this->assertStringContainsString('Staff workspace', $adminSidebar);
        $this->assertStringContainsString('Operations menu', $adminSidebar);
    }
}
