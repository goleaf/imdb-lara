<?php

namespace Tests\Feature\Feature;

use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class TitleParentsGuideExperienceTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_title_parents_guide_page_renders_the_remote_catalog_advisory_shell(): void
    {
        $title = $this->sampleTitleWithParentsGuide();

        $this->get(route('public.titles.parents-guide', $title))
            ->assertOk()
            ->assertSee($title->name)
            ->assertSee('Parents Guide')
            ->assertSeeHtml('data-slot="title-parents-hero"')
            ->assertSeeHtml('data-slot="title-parent-advisories"')
            ->assertSeeHtml('data-slot="title-parent-certificates"')
            ->assertSeeHtml('data-slot="title-parent-spoilers"')
            ->assertSeeHtml('text-[#f4eee5] decoration-white/20 hover:text-white')
            ->assertSee('Content Concerns')
            ->assertSee('Spoiler Notes');
    }

    public function test_title_detail_page_links_to_the_parents_guide_route(): void
    {
        $title = $this->sampleTitleWithParentsGuide();

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee(route('public.titles.parents-guide', $title), false)
            ->assertSee('Parents Guide');
    }
}
