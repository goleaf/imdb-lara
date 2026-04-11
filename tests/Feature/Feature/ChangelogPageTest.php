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
            ->assertDontSee('Portal Changes')
            ->assertDontSee('Screenbase Changelog')
            ->assertSee('What Was Improved')
            ->assertSee('Files That Changed')
            ->assertDontSee('Reading note')
            ->assertDontSee('Markdown stays the source of truth')
            ->assertSeeHtml('data-slot="changes-page-shell"')
            ->assertSeeHtml('data-slot="changes-entry"');
    }
}
