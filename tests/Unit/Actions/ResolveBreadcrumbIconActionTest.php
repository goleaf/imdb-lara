<?php

namespace Tests\Unit\Actions;

use App\Actions\Layout\ResolveBreadcrumbIconAction;
use Tests\TestCase;

class ResolveBreadcrumbIconActionTest extends TestCase
{
    public function test_it_resolves_icons_from_exact_breadcrumb_labels(): void
    {
        $action = app(ResolveBreadcrumbIconAction::class);

        $this->assertSame('home', $action->handle('Home', '/'));
        $this->assertSame('rectangle-stack', $action->handle('Keywords &amp; Connections', '/titles/heat/metadata'));
        $this->assertSame('clipboard-document-check', $action->handle('Contributions Queue', '/admin/contributions'));
    }

    public function test_it_resolves_icons_from_breadcrumb_paths_for_dynamic_records(): void
    {
        $action = app(ResolveBreadcrumbIconAction::class);

        $this->assertSame('film', $action->handle('Heat', 'https://screenbase.test/titles/tt0113277'));
        $this->assertSame('user', $action->handle('Meryl Streep', 'https://screenbase.test/people/nm0000658'));
        $this->assertSame('queue-list', $action->handle('Friday Night Picks', 'https://screenbase.test/lists/dana/friday-night-picks'));
    }

    public function test_it_distinguishes_admin_and_account_dashboard_breadcrumbs(): void
    {
        $action = app(ResolveBreadcrumbIconAction::class);

        $this->assertSame('chart-bar-square', $action->handle('Dashboard', 'https://screenbase.test/admin/dashboard'));
        $this->assertSame('home', $action->handle('Dashboard', 'https://screenbase.test/account/dashboard'));
    }
}
