<?php

namespace Tests\Feature\Feature\Seo;

use App\Enums\TitleType;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class CatalogPageMetadataTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_title_pages_emit_canonical_and_open_graph_metadata_for_the_remote_catalog(): void
    {
        $title = $this->sampleTitle();
        $expectedOpenGraphType = in_array($title->title_type, [TitleType::Series, TitleType::MiniSeries], true)
            ? 'video.tv_show'
            : 'video.movie';

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee('<title>'.e($title->meta_title).' · Screenbase</title>', false)
            ->assertSee('<link rel="canonical" href="'.route('public.titles.show', $title).'">', false)
            ->assertSee('<meta property="og:type" content="'.$expectedOpenGraphType.'">', false);
    }

    public function test_trailers_page_emits_catalog_only_metadata(): void
    {
        $this->get(route('public.trailers.latest'))
            ->assertOk()
            ->assertSee('<title>Trailers · Screenbase</title>', false)
            ->assertSee('<meta name="description" content="Browse trailer-linked titles, clips, and featurettes from the imported Screenbase catalog.">', false)
            ->assertSee('<link rel="canonical" href="'.route('public.trailers.latest').'">', false);
    }

    public function test_title_media_page_emits_canonical_and_open_graph_metadata(): void
    {
        $title = $this->sampleTitleWithMedia();
        $expectedOpenGraphType = in_array($title->title_type, [TitleType::Series, TitleType::MiniSeries], true)
            ? 'video.tv_show'
            : 'video.movie';

        $this->get(route('public.titles.media', $title))
            ->assertOk()
            ->assertSee('<title>'.e($title->name.' Media Gallery').' · Screenbase</title>', false)
            ->assertSee('<link rel="canonical" href="'.route('public.titles.media', $title).'">', false)
            ->assertSee('<meta property="og:type" content="'.$expectedOpenGraphType.'">', false);
    }

    public function test_title_cast_page_emits_canonical_and_open_graph_metadata(): void
    {
        $title = $this->sampleTitleWithCredits();
        $expectedOpenGraphType = in_array($title->title_type, [TitleType::Series, TitleType::MiniSeries], true)
            ? 'video.tv_show'
            : 'video.movie';

        $this->get(route('public.titles.cast', $title))
            ->assertOk()
            ->assertSee('<title>'.e($title->name.' Full Cast').' · Screenbase</title>', false)
            ->assertSee('<link rel="canonical" href="'.route('public.titles.cast', $title).'">', false)
            ->assertSee('<meta property="og:type" content="'.$expectedOpenGraphType.'">', false);
    }
}
