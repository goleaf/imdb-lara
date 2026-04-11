<?php

namespace Tests\Feature\Feature;

use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class ChangelogPageTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_changes_page_renders_the_editorial_markdown_experience(): void
    {
        $response = $this->get(route('public.changes'));

        $response
            ->assertOk()
            ->assertSee('What Was Improved')
            ->assertSee('Files That Changed')
            ->assertDontSee('Release Log')
            ->assertDontSee('Recent Drops')
            ->assertDontSee('Publishing Flow')
            ->assertDontSeeHtml('sb-changelog-hero')
            ->assertSeeHtml('data-slot="changes-page-shell"')
            ->assertSeeHtml('data-slot="changes-entry"');
    }
}
