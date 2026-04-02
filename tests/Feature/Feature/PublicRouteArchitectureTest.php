<?php

namespace Tests\Feature\Feature;

use App\Enums\TitleType;
use App\Models\Genre;
use App\Models\Person;
use App\Models\Review;
use App\Models\Season;
use App\Models\Title;
use App\Models\UserList;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicRouteArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_routes_render_the_expected_imdb_style_page_families(): void
    {
        $this->seed(DemoCatalogSeeder::class);
        Livewire::withoutLazyLoading();

        $movie = Title::query()->where('slug', 'northern-signal')->firstOrFail();
        $series = Title::query()->where('slug', 'static-bloom')->firstOrFail();
        $genre = Genre::query()->where('slug', 'sci-fi')->firstOrFail();
        $person = Person::query()->where('slug', 'ava-mercer')->firstOrFail();
        $season = Season::query()->where('slug', 'static-bloom-season-1')->firstOrFail();
        $episode = Title::query()
            ->where('slug', 'static-bloom-pilot')
            ->with('episodeMeta')
            ->firstOrFail();
        $latestReview = Review::query()
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->firstOrFail();
        $publicList = UserList::query()
            ->where('slug', 'weekend-marathon')
            ->with('user')
            ->firstOrFail();

        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('Hero Spotlight')
            ->assertSee('Trending Now')
            ->assertSee('Northern Signal');

        $this->get(route('public.movies.index'))
            ->assertOk()
            ->assertSee('Browse Movies')
            ->assertSee($movie->name);

        $this->get(route('public.series.index'))
            ->assertOk()
            ->assertSee('Browse TV Shows')
            ->assertSee($series->name);

        $this->get(route('public.people.index'))
            ->assertOk()
            ->assertSee('Browse People')
            ->assertSee('Actors')
            ->assertSee($person->name);

        $this->get(route('public.awards.index'))
            ->assertOk()
            ->assertSee('Awards Archive')
            ->assertSee('2025 Celestial Screen Awards')
            ->assertSee('Best Picture')
            ->assertSee('Winner')
            ->assertSee('Northern Signal')
            ->assertSee('Ava Mercer');

        $this->get(route('public.lists.index'))
            ->assertOk()
            ->assertSee('Browse Public Lists')
            ->assertSeeHtml('data-slot="public-lists-shell"')
            ->assertSee('Weekend Marathon')
            ->assertSee($publicList->user->name);

        $this->get(route('public.genres.show', $genre))
            ->assertOk()
            ->assertSee($genre->name)
            ->assertSee('Northern Signal');

        $this->get(route('public.years.show', ['year' => $movie->release_year]))
            ->assertOk()
            ->assertSee((string) $movie->release_year)
            ->assertSee($movie->name);

        $this->get(route('public.titles.show', $movie))
            ->assertOk()
            ->assertSee($movie->name)
            ->assertSee('Ava Mercer')
            ->assertSee('Elegant and sharp.')
            ->assertSee('Ratings breakdown')
            ->assertSee('Quick facts');

        $this->get(route('public.titles.cast', $movie))
            ->assertOk()
            ->assertSee($movie->name)
            ->assertSee('Archive record')
            ->assertSee('Principal cast')
            ->assertSee('Ava Mercer')
            ->assertSee('Dr. Mara Elling')
            ->assertSee('Creative leads')
            ->assertSee('Technical departments')
            ->assertSee('Director');

        $this->get(route('public.titles.cast', $series))
            ->assertOk()
            ->assertSee($series->name)
            ->assertSee('Archive record')
            ->assertSee('Creative leads')
            ->assertSee('Writing')
            ->assertSee('Micah Stone')
            ->assertSee('Episode Writer')
            ->assertSee('Episode-specific credit')
            ->assertSee('Static Bloom: Switchback');

        $this->get(route('public.titles.media', $movie))
            ->assertOk()
            ->assertSee($movie->name)
            ->assertSee('Media Gallery')
            ->assertSeeHtml('data-slot="title-media-hero"')
            ->assertSee('Posters')
            ->assertSee('Backdrops')
            ->assertSee('Trailers');

        $this->get(route('public.titles.metadata', $movie))
            ->assertOk()
            ->assertSee($movie->name)
            ->assertSee('Keywords & Connections')
            ->assertSeeHtml('data-slot="title-keyword-map"')
            ->assertSeeHtml('data-slot="title-connection-map"')
            ->assertSee('Keyword Map')
            ->assertSee('Title Connections');

        $this->get(route('public.titles.box-office', $movie))
            ->assertOk()
            ->assertSee($movie->name)
            ->assertSee('Box Office Report')
            ->assertSeeHtml('data-slot="title-box-office-hero"')
            ->assertSeeHtml('data-slot="title-box-office-metrics"')
            ->assertSeeHtml('data-slot="title-box-office-ranks"')
            ->assertSeeHtml('data-slot="title-box-office-markets"');

        $this->get(route('public.titles.parents-guide', $movie))
            ->assertOk()
            ->assertSee($movie->name)
            ->assertSee('Parents Guide')
            ->assertSeeHtml('data-slot="title-parent-advisories"')
            ->assertSee('Content Concerns')
            ->assertSee('Certification');

        $this->get(route('public.titles.trivia', $movie))
            ->assertOk()
            ->assertSee($movie->name)
            ->assertSee('Trivia & Goofs')
            ->assertSeeHtml('data-slot="title-trivia-tabs"')
            ->assertSeeHtml('data-slot="title-goof-cards"');

        $this->get(route('public.people.show', $person))
            ->assertOk()
            ->assertSee($person->name)
            ->assertSee('Known for')
            ->assertSee('Awards summary')
            ->assertSee('Trademarks')
            ->assertSee('Filmography')
            ->assertSee('Northern Signal');

        $this->get(route('public.seasons.show', ['series' => $series, 'season' => $season]))
            ->assertOk()
            ->assertSee($season->name)
            ->assertSeeHtml('data-slot="season-browser-hero"')
            ->assertSeeHtml('data-slot="season-browser-episodes"')
            ->assertSee('Static Bloom: Pilot')
            ->assertSee('Episode browser')
            ->assertSee('Top-rated episodes this season');

        $this->get(route('public.episodes.show', ['series' => $series, 'season' => $season, 'episode' => $episode]))
            ->assertOk()
            ->assertSee($episode->name)
            ->assertSee($series->name)
            ->assertSeeHtml('data-slot="episode-detail-hero"')
            ->assertSee('Episode navigation')
            ->assertSee('Parents guide preview')
            ->assertSee('Season lineup');

        $this->get(route('public.search', ['q' => 'Signal']))
            ->assertOk()
            ->assertSee('Search')
            ->assertDontSeeHtml('data-slot="site-footer"')
            ->assertSee('Northern Signal');

        $this->get(route('public.rankings.movies'))
            ->assertOk()
            ->assertSee('Top Rated Movies')
            ->assertSeeHtml('data-slot="chart-legend"')
            ->assertSeeHtml('data-slot="chart-rank-number"')
            ->assertSee($movie->name);

        $this->get(route('public.rankings.series'))
            ->assertOk()
            ->assertSee('Top Rated Series')
            ->assertSeeHtml('data-slot="chart-rank-number"')
            ->assertSee($series->name);

        $this->get(route('public.trending'))
            ->assertOk()
            ->assertSee('Trending')
            ->assertSeeHtml('data-slot="chart-movement"')
            ->assertSee('Northern Signal');

        $this->get(route('public.rankings.movies', ['country' => $movie->origin_country]))
            ->assertOk()
            ->assertSee('Local Charts')
            ->assertSeeHtml('data-slot="chart-location-banner"')
            ->assertSeeHtml('data-slot="chart-context-shell"')
            ->assertSee('Compared with global')
            ->assertSeeHtml('data-flag-type="country"')
            ->assertSee($movie->name);

        $this->get(route('public.trailers.latest'))
            ->assertOk()
            ->assertSee('Latest Trailers')
            ->assertSee('Northern Signal');

        $this->get(route('public.reviews.latest'))
            ->assertOk()
            ->assertSee('Latest Reviews')
            ->assertSee($latestReview->headline);

        $this->get(route('public.lists.show', [$publicList->user, $publicList]))
            ->assertOk()
            ->assertSee($publicList->name)
            ->assertSee($publicList->user->name);

        $this->get(route('public.users.show', $publicList->user))
            ->assertOk()
            ->assertSee($publicList->user->name)
            ->assertSee($publicList->name);
    }

    public function test_episode_titles_redirect_to_their_canonical_nested_episode_routes(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $episode = Title::query()
            ->where('title_type', TitleType::Episode)
            ->with('episodeMeta.season', 'episodeMeta.series')
            ->firstOrFail();

        $this->get(route('public.titles.show', $episode))
            ->assertRedirect(route('public.episodes.show', [
                'series' => $episode->episodeMeta->series,
                'season' => $episode->episodeMeta->season,
                'episode' => $episode,
            ]));
    }

    public function test_chart_country_lens_links_preserve_existing_pagination_query_parameters(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $movie = Title::query()
            ->publishedCatalog()
            ->whereNotNull('origin_country')
            ->orderBy('popularity_rank')
            ->firstOrFail();

        $response = $this->get(route('public.rankings.movies', [
            'top-rated-movies' => 2,
            'country' => $movie->origin_country,
        ]));

        $response
            ->assertOk()
            ->assertSee(route('public.rankings.movies', ['top-rated-movies' => 2]), false);
    }
}
