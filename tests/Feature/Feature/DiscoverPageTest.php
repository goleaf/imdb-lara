<?php

namespace Tests\Feature\Feature;

use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class DiscoverPageTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_discover_page_ignores_invalid_title_type_query_values(): void
    {
        $this->get(route('public.discover', ['type' => 'not-a-real-type']))
            ->assertOk()
            ->assertSee('Filter Panel')
            ->assertSee('Loading')
            ->assertDontSee('1 active');
    }

    public function test_discover_page_preserves_theme_in_the_canonical_url(): void
    {
        $theme = 'mind-bending-ic1';

        $this->get(route('public.discover', ['theme' => $theme]))
            ->assertOk()
            ->assertSeeHtml('href="'.route('public.discover', ['theme' => $theme]).'"');
    }
}
