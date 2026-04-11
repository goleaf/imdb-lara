<?php

namespace Tests\Feature\Feature;

use Livewire\Livewire;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class ProductionReadinessSmokeTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_public_catalog_surfaces_render_with_the_shared_shell(): void
    {
        Livewire::withoutLazyLoading();

        $title = $this->sampleTitle();
        $person = $this->samplePerson();
        $genre = $this->sampleGenre();
        $year = $this->sampleReleaseYear();
        $seriesHierarchy = $this->sampleSeriesHierarchy();

        $routes = [
            route('public.home'),
            route('public.discover'),
            route('public.movies.index'),
            route('public.series.index'),
            route('public.titles.index'),
            route('public.people.index'),
            route('public.awards.index'),
            route('public.trailers.latest'),
            route('public.trending'),
            route('public.genres.show', $genre),
            route('public.years.show', ['year' => $year]),
            route('public.search', ['q' => $this->searchTermFor($title)]),
            route('public.titles.show', $title),
            route('public.people.show', $person),
        ];

        if ($seriesHierarchy !== null) {
            $routes[] = route('public.seasons.show', [
                'series' => $seriesHierarchy['series'],
                'season' => $seriesHierarchy['season'],
            ]);
            $routes[] = route('public.episodes.show', [
                'series' => $seriesHierarchy['series'],
                'season' => $seriesHierarchy['season'],
                'episode' => $seriesHierarchy['episode'],
            ]);
        }

        foreach ($routes as $route) {
            $response = $this->get($route);

            $this->assertSame(200, $response->getStatusCode(), $route);
            $response
                ->assertSee('Screenbase', false)
                ->assertDontSee('Watchlist')
                ->assertDontSee('Create account')
                ->assertDontSee('Sign in');
        }
    }

    public function test_public_catalog_shell_keeps_global_search_and_footer_navigation_alive(): void
    {
        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('Search The Global Catalog')
            ->assertSee('Movies')
            ->assertSee('TV Shows')
            ->assertSee('Trending')
            ->assertSee('Awards');
    }
}
