<?php

namespace Tests\Feature\Feature;

use App\Models\Season;
use App\Models\Title;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TvHierarchyExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_series_title_page_surfaces_latest_season_and_top_rated_episode_blocks(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $series = Title::query()->where('slug', 'static-bloom')->firstOrFail();
        $movie = Title::query()->where('slug', 'northern-signal')->firstOrFail();

        $this->get(route('public.titles.show', $series))
            ->assertOk()
            ->assertSee('Series guide')
            ->assertSee('Latest season overview')
            ->assertSee('Top-rated episodes')
            ->assertSee('Static Bloom: Signal Path')
            ->assertSee('Static Bloom: Pilot');

        $this->get(route('public.titles.show', $movie))
            ->assertOk()
            ->assertDontSee('Latest season overview')
            ->assertDontSee('Top-rated episodes');
    }

    public function test_season_page_renders_episode_guide_navigation_and_rankings(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $series = Title::query()->where('slug', 'static-bloom')->firstOrFail();
        $season = Season::query()->where('slug', 'static-bloom-season-1')->firstOrFail();

        $this->get(route('public.seasons.show', ['series' => $series, 'season' => $season]))
            ->assertOk()
            ->assertSee('Season watch progress')
            ->assertSee('Episode guide')
            ->assertSee('Top-rated episodes this season')
            ->assertSee('Season navigation')
            ->assertSee('Static Bloom: Pilot')
            ->assertSee('Static Bloom: Switchback');
    }

    public function test_episode_page_renders_navigation_guest_cast_and_interaction_sections(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $series = Title::query()->where('slug', 'static-bloom')->firstOrFail();
        $season = Season::query()->where('slug', 'static-bloom-season-1')->firstOrFail();
        $episode = Title::query()->where('slug', 'static-bloom-pilot')->firstOrFail();

        $this->get(route('public.episodes.show', [
            'series' => $series,
            'season' => $season,
            'episode' => $episode,
        ]))
            ->assertOk()
            ->assertSee('Episode navigation')
            ->assertSee('Guest cast')
            ->assertSee('Key crew')
            ->assertSee('Season lineup')
            ->assertSee('Your rating')
            ->assertSee('Write a review')
            ->assertSee('Next episode');
    }

    public function test_episode_route_rejects_mismatched_hierarchy_pairs(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $wrongSeries = Title::query()->where('slug', 'harbor-nine')->firstOrFail();
        $season = Season::query()->where('slug', 'static-bloom-season-1')->firstOrFail();
        $episode = Title::query()->where('slug', 'static-bloom-pilot')->firstOrFail();

        $this->get(route('public.episodes.show', [
            'series' => $wrongSeries,
            'season' => $season,
            'episode' => $episode,
        ]))->assertNotFound();
    }
}
