<?php

namespace Tests\Feature\Feature\Seo;

use App\Models\Title;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class SitemapAndRobotsTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_sitemap_and_robots_include_public_catalog_endpoints(): void
    {
        $genre = $this->sampleGenre();
        $interestCategory = $this->sampleInterestCategory();
        $title = Title::query()
            ->select(['id', 'tconst', 'primarytitle', 'isadult', 'startyear'])
            ->publishedCatalog()
            ->orderByDesc('startyear')
            ->orderBy('primarytitle')
            ->firstOrFail();
        $year = $this->sampleReleaseYear();

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(route('public.home'), false)
            ->assertSee(route('public.awards.index'), false)
            ->assertSee(route('public.trailers.latest'), false)
            ->assertSee(route('public.interest-categories.index'), false)
            ->assertSee(route('public.interest-categories.show', $interestCategory), false)
            ->assertSee(route('public.genres.show', $genre), false)
            ->assertSee(route('public.years.show', ['year' => $year]), false)
            ->assertSee(route('public.titles.cast', $title), false)
            ->assertSee(route('public.titles.media', $title), false)
            ->assertSee(route('public.titles.box-office', $title), false)
            ->assertSee(route('public.titles.parents-guide', $title), false)
            ->assertSee(route('public.titles.trivia', $title), false)
            ->assertSee(route('public.titles.metadata', $title), false)
            ->assertSee('/titles/', false)
            ->assertSee('/people/', false)
            ->assertDontSee('/lists/', false)
            ->assertDontSee('/users/', false)
            ->assertDontSee(route('public.search'), false);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: '.route('sitemap'));
    }
}
