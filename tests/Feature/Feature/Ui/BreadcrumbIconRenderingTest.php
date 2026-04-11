<?php

namespace Tests\Feature\Feature\Ui;

use Tests\TestCase;

class BreadcrumbIconRenderingTest extends TestCase
{
    public function test_breadcrumb_item_renders_a_resolved_icon_without_an_explicit_icon_prop(): void
    {
        $rendered = (string) $this->blade(
            '<x-ui.breadcrumbs.item href="/people" :separator="false">People</x-ui.breadcrumbs.item>',
        );

        $this->assertSame(1, substr_count($rendered, 'data-slot="icon"'));
    }
}
