<?php

namespace Tests\Feature\Feature;

use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class TitleTriviaAndGoofsExperienceTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_title_trivia_and_goofs_page_renders_the_remote_catalog_archive_shell(): void
    {
        $title = $this->sampleTitle();

        $this->get(route('public.titles.trivia', $title))
            ->assertOk()
            ->assertSee($title->name)
            ->assertSee('Trivia & Goofs')
            ->assertSeeHtml('data-slot="title-trivia-hero"')
            ->assertSeeHtml('data-slot="title-trivia-tabs"')
            ->assertSeeHtml('data-slot="title-trivia-cards"')
            ->assertSeeHtml('data-slot="title-goof-cards"')
            ->assertSeeHtml('text-[#f4eee5] decoration-white/20 hover:text-white')
            ->assertSee('Archive Notes');
    }

    public function test_title_detail_page_links_to_the_trivia_route(): void
    {
        $title = $this->sampleTitle();

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee(route('public.titles.trivia', $title), false)
            ->assertSee('Trivia & Goofs');
    }
}
