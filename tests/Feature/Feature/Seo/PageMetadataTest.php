<?php

namespace Tests\Feature\Feature\Seo;

use App\Enums\TitleType;
use Illuminate\Support\Arr;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class PageMetadataTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_title_pages_emit_open_graph_and_breadcrumb_metadata(): void
    {
        $title = $this->sampleTitleWithMedia();
        $expectedOpenGraphType = in_array($title->title_type, [TitleType::Series, TitleType::MiniSeries], true)
            ? 'video.tv_show'
            : 'video.movie';
        $expectedDocumentTitle = ($title->meta_title ?: $title->name).' · Screenbase';

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee('<title>'.e($expectedDocumentTitle).'</title>', false)
            ->assertSee('<meta name="description" content="', false)
            ->assertSee('<link rel="canonical" href="'.route('public.titles.show', $title).'">', false)
            ->assertSee('<meta property="og:type" content="'.$expectedOpenGraphType.'">', false)
            ->assertSee('BreadcrumbList')
            ->assertSee(route('public.titles.show', $title), false);
    }

    public function test_search_and_browse_pages_generate_pagination_aware_canonical_urls(): void
    {
        $title = $this->sampleTitle()->loadMissing('genres');
        $genre = $title->genres->firstOrFail();
        $searchQuery = [
            'genre' => $genre->slug,
            'q' => $this->searchTermFor($title),
            'sort' => 'rating',
            'titles' => 2,
        ];
        ksort($searchQuery);
        $expectedSearchCanonical = route('public.search').'?'.Arr::query($searchQuery);

        $this->get(route('public.search', [
            'q' => $this->searchTermFor($title),
            'genre' => $genre->slug,
            'sort' => 'rating',
            'titles' => 2,
        ]))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex,follow">', false)
            ->assertSee('href="'.e($expectedSearchCanonical).'"', false);

        $this->get(route('public.movies.index', ['movies' => 2]))
            ->assertOk()
            ->assertSee('<meta name="robots" content="index,follow">', false)
            ->assertSee('href="'.e(route('public.movies.index', ['movies' => 2])).'"', false)
            ->assertSee('<title>Browse Movies - Page 2 · Screenbase</title>', false);
    }

    public function test_person_pages_emit_entity_metadata_with_catalog_images(): void
    {
        $person = $this->samplePersonWithHeadshot();

        $response = $this->get(route('public.people.show', $person));

        $response
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('public.people.show', $person).'">', false)
            ->assertSee('<meta property="og:type" content="profile">', false)
            ->assertSee('BreadcrumbList');

        if ($person->preferredHeadshot()?->url) {
            $response->assertSee($person->preferredHeadshot()->url);
        }
    }

    public function test_catalog_and_search_pages_use_current_robots_defaults(): void
    {
        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="index,follow">', false);

        $this->get(route('public.search', [
            'q' => 'zzzzzz-not-a-real-imdb-record',
        ]))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex,follow">', false);
    }
}
