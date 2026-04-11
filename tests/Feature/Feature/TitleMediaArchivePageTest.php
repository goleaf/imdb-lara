<?php

namespace Tests\Feature\Feature;

use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class TitleMediaArchivePageTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_image_archive_page_renders_the_shared_lightbox_gallery_surface(): void
    {
        $title = $this->sampleTitleWithMedia();

        $this->get(route('public.titles.media.archive', [
            'title' => $title,
            'archive' => 'posters',
        ]))
            ->assertOk()
            ->assertSee($title->name)
            ->assertSee('Posters')
            ->assertSeeHtml('data-slot="title-media-archive-hero"')
            ->assertSeeHtml('data-slot="title-media-archive-grid"')
            ->assertSeeHtml('data-slot="title-media-lightbox"')
            ->assertSee('Close lightbox');
    }

    public function test_trailer_archive_page_renders_the_detailed_video_surface(): void
    {
        $title = $this->sampleTitleWithMedia();

        $this->get(route('public.titles.media.archive', [
            'title' => $title,
            'archive' => 'trailers',
        ]))
            ->assertOk()
            ->assertSee($title->name)
            ->assertSee('Trailers')
            ->assertSeeHtml('data-slot="title-media-archive-trailers"');
    }
}
