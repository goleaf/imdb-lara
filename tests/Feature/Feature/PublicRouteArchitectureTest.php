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
            ->assertSee($person->name);

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
            ->assertSee('Where to watch');

        $this->get(route('public.titles.cast', $movie))
            ->assertOk()
            ->assertSee('Full Cast')
            ->assertSee('Ava Mercer');

        $this->get(route('public.people.show', $person))
            ->assertOk()
            ->assertSee($person->name)
            ->assertSee('Actor')
            ->assertSee('Northern Signal');

        $this->get(route('public.seasons.show', ['series' => $series, 'season' => $season]))
            ->assertOk()
            ->assertSee($season->name)
            ->assertSee('Static Bloom: Pilot')
            ->assertSee('Episode guide')
            ->assertSee('Top-rated episodes this season');

        $this->get(route('public.episodes.show', ['series' => $series, 'season' => $season, 'episode' => $episode]))
            ->assertOk()
            ->assertSee($episode->name)
            ->assertSee($series->name)
            ->assertSee('Episode navigation')
            ->assertSee('Season lineup');

        $this->get(route('public.search', ['q' => 'Signal']))
            ->assertOk()
            ->assertSee('Search')
            ->assertSee('Northern Signal');

        $this->get(route('public.rankings.movies'))
            ->assertOk()
            ->assertSee('Top Rated Movies')
            ->assertSee($movie->name);

        $this->get(route('public.rankings.series'))
            ->assertOk()
            ->assertSee('Top Rated Series')
            ->assertSee($series->name);

        $this->get(route('public.trending'))
            ->assertOk()
            ->assertSee('Trending')
            ->assertSee('Northern Signal');

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
}
