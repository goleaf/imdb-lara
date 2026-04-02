<?php

namespace Tests\Feature\Feature\Livewire;

use App\Enums\TitleType;
use App\Livewire\Search\DiscoveryFilters;
use App\Models\Genre;
use App\Models\Title;
use App\Models\TitleStatistic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DiscoveryFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_discovery_filters_render_combobox_controls_instead_of_native_selects(): void
    {
        Genre::factory()->create([
            'name' => 'Sci-Fi',
            'slug' => 'sci-fi',
        ]);

        Livewire::test(DiscoveryFilters::class)
            ->assertSeeHtml('data-slot="discover-active-filters"')
            ->assertSeeHtml('data-slot="autocomplete"')
            ->assertSeeHtml('data-slot="combobox-input"')
            ->assertDontSeeHtml('<select');
    }

    public function test_discovery_filters_render_title_autocomplete_suggestions_for_matching_titles(): void
    {
        Title::factory()->create([
            'name' => 'Northern Signal',
            'search_keywords' => 'signal, sci-fi',
            'is_published' => true,
        ]);
        Title::factory()->create([
            'name' => 'Signal North',
            'search_keywords' => 'signal, thriller',
            'is_published' => true,
        ]);

        Livewire::test(DiscoveryFilters::class)
            ->set('search', 'Signal')
            ->assertSeeHtml('data-slot="autocomplete-item"')
            ->assertSee('Northern Signal')
            ->assertSee('Signal North');
    }

    public function test_discovery_filters_search_by_text_genre_and_rating(): void
    {
        $sciFi = Genre::factory()->create([
            'name' => 'Sci-Fi',
            'slug' => 'sci-fi',
        ]);
        $drama = Genre::factory()->create([
            'name' => 'Drama',
            'slug' => 'drama',
        ]);

        $northernSignal = Title::factory()->movie()->create(['name' => 'Northern Signal']);
        $mercuryVale = Title::factory()->series()->create(['name' => 'Mercury Vale']);

        $northernSignal->genres()->attach($sciFi);
        $mercuryVale->genres()->attach($drama);

        TitleStatistic::factory()->for($northernSignal)->create([
            'average_rating' => 8.4,
        ]);
        TitleStatistic::factory()->for($mercuryVale)->create([
            'average_rating' => 6.1,
        ]);

        Livewire::test(DiscoveryFilters::class)
            ->set('search', 'Northern')
            ->assertSee('Northern Signal')
            ->assertDontSee('Mercury Vale')
            ->set('search', '')
            ->set('genre', 'sci-fi')
            ->assertSee('Northern Signal')
            ->assertDontSee('Mercury Vale')
            ->set('genre', null)
            ->set('type', TitleType::Movie->value)
            ->assertSee('Northern Signal')
            ->assertDontSee('Mercury Vale')
            ->set('type', null)
            ->set('minimumRating', 8)
            ->assertSee('Northern Signal')
            ->assertDontSee('Mercury Vale');
    }

    public function test_discovery_filters_support_awards_release_runtime_language_and_country_filters(): void
    {
        $awardWinner = Title::factory()->movie()->create([
            'name' => 'Northern Signal',
            'release_year' => 2024,
            'release_date' => '2024-09-18',
            'runtime_minutes' => 132,
            'original_language' => 'en',
            'origin_country' => 'US',
        ]);
        $awardNominee = Title::factory()->movie()->create([
            'name' => 'Mercury Vale',
            'release_year' => 2024,
            'release_date' => '2024-05-10',
            'runtime_minutes' => 98,
            'original_language' => 'en',
            'origin_country' => 'GB',
        ]);
        $arthouseClassic = Title::factory()->movie()->create([
            'name' => 'Paris After Rain',
            'release_year' => 2002,
            'release_date' => '2002-02-14',
            'runtime_minutes' => 141,
            'original_language' => 'fr',
            'origin_country' => 'FR',
        ]);

        TitleStatistic::factory()->for($awardWinner)->create([
            'average_rating' => 8.8,
            'rating_count' => 1800,
            'awards_won_count' => 3,
            'awards_nominated_count' => 6,
        ]);
        TitleStatistic::factory()->for($awardNominee)->create([
            'average_rating' => 7.4,
            'rating_count' => 600,
            'awards_won_count' => 0,
            'awards_nominated_count' => 4,
        ]);
        TitleStatistic::factory()->for($arthouseClassic)->create([
            'average_rating' => 9.1,
            'rating_count' => 2200,
            'awards_won_count' => 5,
            'awards_nominated_count' => 8,
        ]);

        Livewire::test(DiscoveryFilters::class)
            ->assertSee('Awards')
            ->assertSee('Release from')
            ->assertSee('Vote count')
            ->assertSee('Country')
            ->set('awards', 'winners')
            ->set('yearFrom', '2020')
            ->set('minimumRating', '8')
            ->set('votesMin', '1000')
            ->set('runtime', '120-plus')
            ->set('language', 'en')
            ->set('country', 'US')
            ->assertSee('Northern Signal')
            ->assertDontSee('Mercury Vale')
            ->assertDontSee('Paris After Rain');
    }

    public function test_discovery_filters_make_active_filter_state_obvious(): void
    {
        Genre::factory()->create([
            'name' => 'Sci-Fi',
            'slug' => 'sci-fi',
        ]);

        Title::factory()->movie()->create([
            'name' => 'Northern Signal',
            'search_keywords' => 'signal, sci-fi',
        ]);

        Livewire::test(DiscoveryFilters::class)
            ->assertSee('All titles')
            ->set('search', 'Signal')
            ->set('genre', 'sci-fi')
            ->assertSee('2 active')
            ->assertSee('Keyword: Signal')
            ->assertSee('Sci-Fi');
    }
}
