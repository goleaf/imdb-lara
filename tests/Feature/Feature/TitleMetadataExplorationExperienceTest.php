<?php

namespace Tests\Feature\Feature;

use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class TitleMetadataExplorationExperienceTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_title_metadata_page_renders_the_remote_catalog_keyword_and_connection_surface(): void
    {
        $title = $this->sampleTitleWithInterests();

        $this->get(route('public.titles.metadata', $title))
            ->assertOk()
            ->assertSee($title->name)
            ->assertSee('Keywords & Connections')
            ->assertSeeHtml('data-slot="title-metadata-hero"')
            ->assertSeeHtml('data-slot="title-keyword-map"')
            ->assertSeeHtml('data-slot="title-connection-map"')
            ->assertSee('Keyword Map')
            ->assertSee('Title Connections');
    }

    public function test_title_detail_page_links_to_the_metadata_route(): void
    {
        $title = $this->sampleTitleWithInterests();

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee(route('public.titles.metadata', $title), false)
            ->assertSee('Keywords & Connections');
    }
}
