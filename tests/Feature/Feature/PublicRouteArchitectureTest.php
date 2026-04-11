<?php

namespace Tests\Feature\Feature;

use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class PublicRouteArchitectureTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_public_routes_render_the_current_catalog_page_families(): void
    {
        Livewire::withoutLazyLoading();

        $movie = $this->sampleTitle();
        $series = $this->sampleSeries();
        $genre = $this->sampleGenre();
        $interestCategory = $this->sampleInterestCategory();
        $person = $this->samplePerson();
        $year = $this->sampleReleaseYear();
        $castTitle = $this->sampleTitleWithCredits();
        $mediaTitle = $this->sampleTitleWithMedia();
        $boxOfficeTitle = $this->sampleTitleWithBoxOffice();
        $parentsGuideTitle = $this->sampleTitleWithParentsGuide();
        $metadataTitle = $this->sampleTitleWithInterests();

        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('Catalog Spotlight')
            ->assertSee('Trending titles');

        $this->get(route('public.movies.index'))
            ->assertOk()
            ->assertSee('Browse Movies');

        $this->get(route('public.series.index'))
            ->assertOk()
            ->assertSee('Browse TV Shows');

        $this->get(route('public.people.index'))
            ->assertOk()
            ->assertSee('Browse People');

        $this->get(route('public.interest-categories.index'))
            ->assertOk()
            ->assertSee('Interest Categories');

        $this->get(route('public.awards.index'))
            ->assertOk()
            ->assertSee('Awards Archive')
            ->assertSeeHtml('data-slot="awards-archive-shell"');

        $this->get(route('public.trailers.latest'))
            ->assertOk()
            ->assertSee('Trailers');

        $this->get(route('public.genres.show', $genre))
            ->assertOk()
            ->assertSee($genre->name);

        $this->get(route('public.years.show', ['year' => $year]))
            ->assertOk()
            ->assertSee((string) $year);

        $this->get(route('public.titles.show', $movie))
            ->assertOk()
            ->assertSee($movie->name)
            ->assertSee('Overview');

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

        $this->get(route('public.titles.trivia', $movie))
            ->assertOk()
            ->assertSee('Trivia & Goofs');

        $this->get(route('public.titles.metadata', $metadataTitle))
            ->assertOk()
            ->assertSee('Keywords & Connections');

        $this->get(route('public.people.show', $person))
            ->assertOk()
            ->assertSee($person->name)
            ->assertSee('Known for')
            ->assertSee('Filmography');

        $this->get(route('public.interest-categories.show', $interestCategory))
            ->assertOk()
            ->assertSee($interestCategory->name)
            ->assertSee('Category overview');

        $this->get(route('public.search', ['q' => $this->searchTermFor($movie)]))
            ->assertOk()
            ->assertSee('Search Results')
            ->assertSee($movie->name);

        $this->get(route('public.rankings.movies'))
            ->assertOk()
            ->assertSee('Top Rated Movies');

        $this->get(route('public.rankings.series'))
            ->assertOk()
            ->assertSee('Top Rated Series');

        $this->get(route('public.trending'))
            ->assertOk()
            ->assertSee('Trending Now');
    }

    public function test_public_route_registry_matches_the_catalog_only_surface(): void
    {
        $this->assertTrue(Route::has('public.home'));
        $this->assertTrue(Route::has('public.awards.index'));
        $this->assertTrue(Route::has('public.trailers.latest'));
        $this->assertTrue(Route::has('public.interest-categories.index'));
        $this->assertTrue(Route::has('public.interest-categories.show'));
        $this->assertTrue(Route::has('public.titles.cast'));
        $this->assertTrue(Route::has('public.titles.media'));
        $this->assertTrue(Route::has('public.titles.box-office'));
        $this->assertTrue(Route::has('public.titles.parents-guide'));
        $this->assertTrue(Route::has('public.titles.trivia'));
        $this->assertTrue(Route::has('public.titles.metadata'));
        $this->assertTrue(Route::has('public.search'));
        $this->assertFalse(Route::has('public.lists.index'));
        $this->assertFalse(Route::has('public.users.show'));
        $this->assertFalse(Route::has('public.reviews.latest'));
    }
}
