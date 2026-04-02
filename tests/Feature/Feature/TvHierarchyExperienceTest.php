<?php

namespace Tests\Feature\Feature;

use App\Models\Episode;
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
            ->assertSeeHtml('data-slot="series-guide-navigation"')
            ->assertSee('Season navigation')
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
            ->assertSeeHtml('data-slot="season-browser-hero"')
            ->assertSeeHtml('data-slot="season-browser-navigation"')
            ->assertSeeHtml('data-slot="season-browser-episodes"')
            ->assertSeeHtml('data-slot="badge-icon"')
            ->assertSeeHtml('data-slot="link-icon:after"')
            ->assertSee('Season watch progress')
            ->assertSee('Episode browser')
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
        $episode = Title::query()->where('slug', 'static-bloom-switchback')->firstOrFail();

        $this->get(route('public.episodes.show', [
            'series' => $series,
            'season' => $season,
            'episode' => $episode,
        ]))
            ->assertOk()
            ->assertSeeHtml('data-slot="episode-detail-hero"')
            ->assertSeeHtml('data-slot="badge-icon"')
            ->assertSee('Episode navigation')
            ->assertSee('Guest cast')
            ->assertSee('Key crew')
            ->assertSee('Parents guide preview')
            ->assertSee('Trivia')
            ->assertSee('Goofs')
            ->assertSee('Season lineup')
            ->assertSee('Your rating')
            ->assertSee('Write a review')
            ->assertSee('href="#episode-rating"', false)
            ->assertSee('href="#episode-review"', false)
            ->assertSee('Previous episode')
            ->assertSee('Next episode');
    }

    public function test_season_route_rejects_mismatched_series_and_season_pairs(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $wrongSeries = Title::query()->where('slug', 'harbor-nine')->firstOrFail();
        $season = Season::query()->where('slug', 'static-bloom-season-1')->firstOrFail();

        $this->get(route('public.seasons.show', [
            'series' => $wrongSeries,
            'season' => $season,
        ]))->assertNotFound();
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

    public function test_episode_page_renders_payload_backed_parents_guide_trivia_and_goofs_sections(): void
    {
        $series = Title::factory()->series()->create([
            'name' => 'Signal Archive',
            'slug' => 'signal-archive',
        ]);

        $season = Season::factory()->for($series, 'series')->create([
            'name' => 'Season 2',
            'slug' => 'signal-archive-season-2',
            'season_number' => 2,
        ]);

        $episode = Title::factory()->episode()->create([
            'name' => 'Signal Archive: Crossfade',
            'slug' => 'signal-archive-crossfade',
            'age_rating' => 'TV-14',
            'imdb_payload' => [
                'parentsGuide' => [
                    'advisories' => [
                        [
                            'category' => 'violence',
                            'severity' => 'moderate',
                            'text' => 'A sustained control-room fight and brief bloody aftermath.',
                        ],
                    ],
                    'spoilers' => [
                        'Late reveal of the signal source.',
                    ],
                ],
                'certificates' => [
                    'certificates' => [
                        [
                            'rating' => 'TV-14',
                            'country' => ['code' => 'US', 'name' => 'United States'],
                            'attributes' => ['violence'],
                        ],
                    ],
                ],
                'trivia' => [
                    'triviaEntries' => [
                        ['text' => 'The waveform wall was built as a practical set.'],
                    ],
                ],
                'goofs' => [
                    'goofEntries' => [
                        ['text' => 'A monitor clock jumps ahead between cuts.'],
                    ],
                ],
            ],
        ]);

        Episode::factory()
            ->for($episode, 'title')
            ->for($series, 'series')
            ->for($season, 'season')
            ->create([
                'season_number' => 2,
                'episode_number' => 4,
            ]);

        $this->get(route('public.episodes.show', [
            'series' => $series,
            'season' => $season,
            'episode' => $episode,
        ]))
            ->assertOk()
            ->assertSee('Parents guide preview')
            ->assertSee('Moderate')
            ->assertSee('A sustained control-room fight and brief bloody aftermath.')
            ->assertSee('Late reveal of the signal source.')
            ->assertSee('The waveform wall was built as a practical set.')
            ->assertSee('A monitor clock jumps ahead between cuts.');
    }
}
