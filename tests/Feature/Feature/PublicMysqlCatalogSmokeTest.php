<?php

namespace Tests\Feature\Feature;

use Livewire\Livewire;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class PublicMysqlCatalogSmokeTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_public_home_and_browse_routes_render_against_the_remote_mysql_catalog(): void
    {
        Livewire::withoutLazyLoading();

        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('Catalog Spotlight')
            ->assertSee('Awards Spotlight')
            ->assertSee('Latest Trailers')
            ->assertSee('Trending titles');

        $this->get(route('public.discover'))
            ->assertOk()
            ->assertSee('Advanced Title Discovery');

        $this->get(route('public.awards.index'))
            ->assertOk()
            ->assertSee('Awards');

        $this->get(route('public.trailers.latest'))
            ->assertOk()
            ->assertSee('Trailers');

        $this->get(route('public.titles.index'))
            ->assertOk()
            ->assertSee('Browse Titles');

        $this->get(route('public.movies.index'))
            ->assertOk()
            ->assertSee('Browse Movies');

        $this->get(route('public.series.index'))
            ->assertOk()
            ->assertSee('Browse TV Shows');

        $this->get(route('public.interest-categories.index'))
            ->assertOk()
            ->assertSee('Interest Categories');
    }

    public function test_public_people_routes_render_against_the_remote_mysql_catalog(): void
    {
        Livewire::withoutLazyLoading();

        $person = $this->samplePerson();
        $interestCategory = $this->sampleInterestCategory();

        $this->get(route('public.people.index'))
            ->assertOk()
            ->assertSee('Browse People')
            ->assertSee('Catalog footprint');

        $this->get(route('public.people.show', $person))
            ->assertOk()
            ->assertSee($person->name)
            ->assertSee('Career profile');

        $this->get(route('public.interest-categories.show', $interestCategory))
            ->assertOk()
            ->assertSee($interestCategory->name)
            ->assertSee('Linked titles');
    }

    public function test_public_search_and_title_routes_render_against_the_remote_mysql_catalog(): void
    {
        Livewire::withoutLazyLoading();

        $title = $this->sampleTitle();
        $series = $this->sampleSeries();
        $castTitle = $this->sampleTitleWithCredits();
        $mediaTitle = $this->sampleTitleWithMedia();
        $boxOfficeTitle = $this->sampleTitleWithBoxOffice();
        $parentsGuideTitle = $this->sampleTitleWithParentsGuide();
        $metadataTitle = $this->sampleTitleWithInterests();
        $seriesHierarchy = $this->sampleSeriesHierarchy();

        $this->get(route('public.search', ['q' => $this->searchTermFor($title)]))
            ->assertOk()
            ->assertSee('Search Results')
            ->assertSee($title->name);

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee($title->name);

        $this->get(route('public.titles.show', $series))
            ->assertOk()
            ->assertSee($series->name);

        $this->get(route('public.titles.cast', $castTitle))
            ->assertOk()
            ->assertSee('Full Cast');

        $this->get(route('public.titles.media', $mediaTitle))
            ->assertOk()
            ->assertSee('Media Gallery');

        $this->get(route('public.titles.box-office', $boxOfficeTitle))
            ->assertOk()
            ->assertSee('Box Office Report');

        $this->get(route('public.titles.parents-guide', $parentsGuideTitle))
            ->assertOk()
            ->assertSee('Parents Guide');

        $this->get(route('public.titles.trivia', $title))
            ->assertOk()
            ->assertSee('Trivia & Goofs');

        $this->get(route('public.titles.metadata', $metadataTitle))
            ->assertOk()
            ->assertSee('Keywords & Connections');

        $this->get(route('public.titles.show', $metadataTitle))
            ->assertOk()
            ->assertSee('Discovery profile');

        if ($seriesHierarchy !== null) {
            $this->get(route('public.seasons.show', [
                'series' => $seriesHierarchy['series'],
                'season' => $seriesHierarchy['season'],
            ]))
                ->assertOk()
                ->assertSee('Episode browser');

            $this->get(route('public.episodes.show', [
                'series' => $seriesHierarchy['series'],
                'season' => $seriesHierarchy['season'],
                'episode' => $seriesHierarchy['episode'],
            ]))
                ->assertOk()
                ->assertSee('Episode navigation');
        }
    }
}
