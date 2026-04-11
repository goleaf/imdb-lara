<?php

namespace Tests\Feature\Feature;

use App\Models\Episode;
use App\Models\Season;
use App\Models\Title;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class TvHierarchyExperienceTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_series_title_page_surfaces_series_guide_blocks_without_affecting_movie_pages(): void
    {
        Livewire::withoutLazyLoading();

        $hierarchy = $this->resolvedSeriesHierarchy();
        $series = $hierarchy['series'];
        $movie = $this->sampleMovie();

        $this->get(route('public.titles.show', $series))
            ->assertOk()
            ->assertSee('Series guide')
            ->assertSeeHtml('data-slot="series-guide-navigation"')
            ->assertSeeHtml('data-slot="series-guide-latest-season"')
            ->assertSeeHtml('data-slot="series-guide-top-episodes"')
            ->assertSee('Season navigation')
            ->assertSee('Latest season overview')
            ->assertSee('Top-rated episodes')
            ->assertSee($hierarchy['season']->name)
            ->assertSee($hierarchy['episode']->name);

        $this->get(route('public.titles.show', $movie))
            ->assertOk()
            ->assertDontSee('Series guide')
            ->assertDontSee('Latest season overview')
            ->assertDontSee('Top-rated episodes');
    }

    public function test_season_page_renders_episode_browser_navigation_and_rankings(): void
    {
        Livewire::withoutLazyLoading();

        $hierarchy = $this->resolvedSeriesHierarchy();
        $series = $hierarchy['series'];
        $season = $hierarchy['season'];
        $episode = $hierarchy['episode'];

        $this->get(route('public.seasons.show', ['series' => $series, 'season' => $season]))
            ->assertOk()
            ->assertSeeHtml('data-slot="season-browser-hero"')
            ->assertSeeHtml('data-slot="season-browser-navigation"')
            ->assertSeeHtml('data-slot="season-browser-episodes"')
            ->assertSee('Season navigation')
            ->assertSee('Episode browser')
            ->assertSee('Top-rated episodes this season')
            ->assertSee($season->name)
            ->assertSee($episode->name);
    }

    public function test_episode_page_renders_current_navigation_facts_and_catalog_credit_sections(): void
    {
        Livewire::withoutLazyLoading();

        $hierarchy = $this->resolvedSeriesHierarchy();
        $series = $hierarchy['series'];
        $season = $hierarchy['season'];
        $episode = $hierarchy['episode'];

        $this->get(route('public.episodes.show', [
            'series' => $series,
            'season' => $season,
            'episode' => $episode,
        ]))
            ->assertOk()
            ->assertSeeHtml('data-slot="episode-detail-hero"')
            ->assertSeeHtml('data-slot="episode-detail-facts"')
            ->assertSeeHtml('data-slot="episode-detail-cast"')
            ->assertSeeHtml('data-slot="episode-detail-lineup"')
            ->assertSee('Episode facts')
            ->assertSee('Guest cast')
            ->assertSee('Key crew')
            ->assertSee('Episode navigation')
            ->assertSee('Season lineup')
            ->assertSee($episode->name);
    }

    public function test_season_route_rejects_mismatched_series_and_season_pairs(): void
    {
        Livewire::withoutLazyLoading();

        $hierarchy = $this->resolvedSeriesHierarchy();
        $wrongSeries = $this->sampleMovie();

        $this->get(route('public.seasons.show', [
            'series' => $wrongSeries,
            'season' => $hierarchy['season'],
        ]))->assertNotFound();
    }

    public function test_episode_route_rejects_mismatched_hierarchy_pairs(): void
    {
        Livewire::withoutLazyLoading();

        $hierarchy = $this->resolvedSeriesHierarchy();
        $wrongSeries = $this->sampleMovie();

        $this->get(route('public.episodes.show', [
            'series' => $wrongSeries,
            'season' => $hierarchy['season'],
            'episode' => $hierarchy['episode'],
        ]))->assertNotFound();
    }

    public function test_episode_page_stays_catalog_only_without_review_or_rating_calls_to_action(): void
    {
        Livewire::withoutLazyLoading();

        $hierarchy = $this->resolvedSeriesHierarchy();

        $this->get(route('public.episodes.show', [
            'series' => $hierarchy['series'],
            'season' => $hierarchy['season'],
            'episode' => $hierarchy['episode'],
        ]))
            ->assertOk()
            ->assertDontSee('Write a review')
            ->assertDontSee('Your rating')
            ->assertDontSee('Parents guide preview');
    }

    /**
     * @return array{series: Title, season: Season, episode: Title, episodeMeta: Episode}
     */
    private function resolvedSeriesHierarchy(): array
    {
        $hierarchy = $this->sampleSeriesHierarchy();

        if ($hierarchy === null) {
            $this->markTestSkipped('Remote MySQL catalog does not currently expose a usable series / season / episode hierarchy for the public TV pages.');
        }

        return $hierarchy;
    }
}
