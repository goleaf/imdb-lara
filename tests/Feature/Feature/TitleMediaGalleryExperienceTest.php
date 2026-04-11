<?php

namespace Tests\Feature\Feature;

use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class TitleMediaGalleryExperienceTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_title_media_gallery_page_renders_the_remote_catalog_archive_surface(): void
    {
        $title = $this->sampleTitleWithMedia();

        $this->get(route('public.titles.media', $title))
            ->assertOk()
            ->assertSee($title->name)
            ->assertSee('Media Gallery')
            ->assertSeeHtml('data-slot="title-media-hero"')
            ->assertSeeHtml('data-slot="title-media-viewer"')
            ->assertSeeHtml('data-slot="title-media-posters"')
            ->assertSeeHtml('data-slot="title-media-stills"')
            ->assertSeeHtml('data-slot="title-media-backdrops"')
            ->assertSeeHtml('data-slot="title-media-trailers"')
            ->assertSeeHtml('data-slot="title-media-trailer-list"')
            ->assertSeeHtml('data-slot="title-media-lightbox"')
            ->assertSee('openLightbox(')
            ->assertSee('Close lightbox')
            ->assertSeeHtml('text-[#f4eee5] decoration-white/20 hover:text-white')
            ->assertSee('Back to title')
            ->assertSee('Browse trailers')
            ->assertDontSee('Watch featured trailer')
            ->assertDontSee('Clips and supporting video records');
    }

    public function test_title_detail_page_links_to_the_media_gallery_route(): void
    {
        $title = $this->sampleTitleWithMedia();

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee(route('public.titles.media', $title), false)
            ->assertSee('Archive Views')
            ->assertSee('Media Gallery');
    }
}
