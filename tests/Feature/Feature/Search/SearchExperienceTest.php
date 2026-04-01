<?php

namespace Tests\Feature\Feature\Search;

use App\Enums\ListVisibility;
use App\Enums\TitleType;
use App\Models\Genre;
use App\Models\ListItem;
use App\Models\Person;
use App\Models\Title;
use App\Models\TitleStatistic;
use App\Models\TitleTranslation;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_page_groups_titles_people_and_lists_for_a_query(): void
    {
        [$movie, $series] = $this->seedSearchDataset();

        $this->get(route('public.search', ['q' => 'Signal']))
            ->assertOk()
            ->assertSee('Search')
            ->assertSee('Title Results')
            ->assertSee('People')
            ->assertSee('Lists')
            ->assertSee($movie->name)
            ->assertSee($series->name)
            ->assertSee('Ava Signal')
            ->assertSee('Signal Essentials');
    }

    public function test_search_matches_translated_titles_and_supports_advanced_title_filters(): void
    {
        [$movie, $series, $endedSeries, $upcomingSeries] = $this->seedSearchDataset();

        $this->get(route('public.search', ['q' => 'Siaures']))
            ->assertOk()
            ->assertSee($movie->name);

        $this->get(route('public.search', [
            'q' => 'Signal',
            'type' => TitleType::Series->value,
            'genre' => 'sci-fi',
            'yearFrom' => 2020,
            'yearTo' => 2025,
            'ratingMin' => 8,
            'ratingMax' => 10,
            'votesMin' => 500,
            'language' => 'en',
            'country' => 'GB',
            'runtime' => '30-60',
            'status' => 'returning',
        ]))
            ->assertOk()
            ->assertSee($series->name)
            ->assertDontSee($movie->name)
            ->assertDontSee($endedSeries->name)
            ->assertDontSee($upcomingSeries->name);
    }

    public function test_search_page_shows_a_no_results_state_when_nothing_matches(): void
    {
        $this->seedSearchDataset();

        $this->get(route('public.search', [
            'q' => 'Nope',
            'country' => 'JP',
        ]))
            ->assertOk()
            ->assertSee('No search results match the current query and filters.');
    }

    /**
     * @return array{0: Title, 1: Title, 2: Title, 3: Title}
     */
    private function seedSearchDataset(): array
    {
        $sciFi = Genre::factory()->create([
            'name' => 'Sci-Fi',
            'slug' => 'sci-fi',
        ]);
        $drama = Genre::factory()->create([
            'name' => 'Drama',
            'slug' => 'drama',
        ]);

        $movie = Title::factory()->movie()->create([
            'name' => 'Northern Signal',
            'original_name' => 'Northern Signal',
            'search_keywords' => 'signal, north, sci-fi',
            'release_year' => 2024,
            'release_date' => '2024-05-10',
            'runtime_minutes' => 115,
            'original_language' => 'en',
            'origin_country' => 'US',
            'popularity_rank' => 12,
            'is_published' => true,
        ]);
        $movie->genres()->attach($sciFi);
        TitleStatistic::factory()->for($movie)->create([
            'average_rating' => 8.7,
            'rating_count' => 740,
            'review_count' => 88,
            'watchlist_count' => 640,
        ]);
        TitleTranslation::factory()->for($movie)->create([
            'locale' => 'lt',
            'localized_title' => 'Siaures Signalas',
            'localized_slug' => 'siaures-signalas',
        ]);

        $series = Title::factory()->series()->create([
            'name' => 'Signal North',
            'original_name' => 'Signal North',
            'search_keywords' => 'signal, series, north',
            'release_year' => 2023,
            'release_date' => '2023-03-02',
            'end_year' => null,
            'runtime_minutes' => 52,
            'original_language' => 'en',
            'origin_country' => 'GB',
            'popularity_rank' => 4,
            'is_published' => true,
        ]);
        $series->genres()->attach($sciFi);
        TitleStatistic::factory()->for($series)->create([
            'average_rating' => 9.2,
            'rating_count' => 915,
            'review_count' => 120,
            'watchlist_count' => 980,
        ]);

        $endedSeries = Title::factory()->series()->create([
            'name' => 'Signal Archive',
            'search_keywords' => 'signal, archive',
            'release_year' => 2018,
            'release_date' => '2018-04-12',
            'end_year' => 2020,
            'runtime_minutes' => 45,
            'original_language' => 'lt',
            'origin_country' => 'LT',
            'popularity_rank' => 20,
            'is_published' => true,
        ]);
        $endedSeries->genres()->attach($drama);
        TitleStatistic::factory()->for($endedSeries)->create([
            'average_rating' => 7.4,
            'rating_count' => 160,
            'review_count' => 26,
            'watchlist_count' => 120,
        ]);

        $upcomingSeries = Title::factory()->series()->create([
            'name' => 'Signal Future',
            'search_keywords' => 'signal, future',
            'release_year' => 2027,
            'release_date' => '2027-01-15',
            'end_year' => null,
            'runtime_minutes' => 49,
            'original_language' => 'en',
            'origin_country' => 'US',
            'popularity_rank' => 8,
            'is_published' => true,
        ]);
        $upcomingSeries->genres()->attach($sciFi);
        TitleStatistic::factory()->for($upcomingSeries)->create([
            'average_rating' => 0,
            'rating_count' => 0,
            'review_count' => 0,
            'watchlist_count' => 210,
        ]);

        Person::factory()->create([
            'name' => 'Ava Signal',
            'alternate_names' => 'Ava Mercer | Signal Maker',
            'search_keywords' => 'signal, actor',
            'is_published' => true,
        ]);

        $curator = User::factory()->create([
            'name' => 'Signal Curator',
            'username' => 'signal-curator',
        ]);
        $list = UserList::factory()->public()->for($curator)->create([
            'name' => 'Signal Essentials',
            'slug' => 'signal-essentials',
            'description' => 'A public list of signal-driven stories.',
            'visibility' => ListVisibility::Public,
        ]);
        ListItem::factory()->for($list, 'userList')->for($movie, 'title')->create([
            'position' => 1,
        ]);

        UserList::factory()->for($curator)->create([
            'name' => 'Signal Private Cuts',
            'slug' => 'signal-private-cuts',
            'description' => 'Should not appear in public search.',
            'visibility' => ListVisibility::Private,
        ]);

        return [$movie, $series, $endedSeries, $upcomingSeries];
    }
}
