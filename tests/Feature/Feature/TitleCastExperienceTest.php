<?php

namespace Tests\Feature\Feature;

use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class TitleCastExperienceTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_title_cast_page_renders_the_remote_catalog_credit_archive_surface(): void
    {
        $title = $this->sampleTitleWithCredits();

        $this->get(route('public.titles.cast', $title))
            ->assertOk()
            ->assertSee($title->name)
            ->assertSee('Full Cast')
            ->assertSeeHtml('data-slot="title-cast-hero"')
            ->assertSeeHtml('data-slot="title-cast-cast-section"')
            ->assertSeeHtml('data-slot="title-cast-crew-section"')
            ->assertSeeHtml('text-[#f4eee5] decoration-white/20 hover:text-white')
            ->assertSee('Back to title')
            ->assertSee('Cast')
            ->assertSee('Crew');
    }

    public function test_title_detail_page_links_to_the_cast_archive_route(): void
    {
        $title = $this->sampleTitleWithCredits();

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee(route('public.titles.cast', $title), false)
            ->assertSee('Archive Views')
            ->assertSee('Full Cast & Crew');
    }
}
