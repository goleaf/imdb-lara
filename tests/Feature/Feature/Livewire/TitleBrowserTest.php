<?php

namespace Tests\Feature\Feature\Livewire;

use App\Enums\TitleType;
use App\Livewire\Catalog\TitleBrowser;
use App\Models\Genre;
use App\Models\Title;
use App\Models\TitleStatistic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TitleBrowserTest extends TestCase
{
    use RefreshDatabase;

    public function test_title_browser_filters_title_collections_for_public_catalog_pages(): void
    {
        $drama = Genre::factory()->create([
            'name' => 'Drama',
            'slug' => 'drama',
        ]);
        $comedy = Genre::factory()->create([
            'name' => 'Comedy',
            'slug' => 'comedy',
        ]);

        $targetMovie = Title::factory()->movie()->create([
            'name' => 'Northern Window',
            'release_year' => 2024,
        ]);
        $otherDramaMovie = Title::factory()->movie()->create([
            'name' => 'Quiet Transit',
            'release_year' => 2023,
        ]);
        $series = Title::factory()->series()->create([
            'name' => 'Network Bloom',
            'release_year' => 2024,
        ]);
        $comedyMovie = Title::factory()->movie()->create([
            'name' => 'Blue Laughter',
            'release_year' => 2024,
        ]);
        $episode = Title::factory()->episode()->create([
            'name' => 'Northern Window: Pilot',
            'release_year' => 2024,
        ]);

        $targetMovie->genres()->attach($drama);
        $otherDramaMovie->genres()->attach($drama);
        $series->genres()->attach($drama);
        $comedyMovie->genres()->attach($comedy);
        $episode->genres()->attach($drama);

        TitleStatistic::factory()->for($targetMovie)->create([
            'average_rating' => 9.4,
            'rating_count' => 2500,
        ]);
        TitleStatistic::factory()->for($otherDramaMovie)->create([
            'average_rating' => 7.2,
            'rating_count' => 1700,
        ]);
        TitleStatistic::factory()->for($series)->create([
            'average_rating' => 9.8,
            'rating_count' => 4100,
        ]);
        TitleStatistic::factory()->for($comedyMovie)->create([
            'average_rating' => 8.8,
            'rating_count' => 900,
        ]);
        TitleStatistic::factory()->for($episode)->create([
            'average_rating' => 9.9,
            'rating_count' => 600,
        ]);

        Livewire::test(TitleBrowser::class, [
            'types' => [TitleType::Movie->value],
            'genre' => 'drama',
            'year' => 2024,
            'sort' => 'rating',
            'pageName' => 'movies',
        ])
            ->assertSee('Northern Window')
            ->assertDontSee('Quiet Transit')
            ->assertDontSee('Network Bloom')
            ->assertDontSee('Blue Laughter')
            ->assertDontSee('Northern Window: Pilot');
    }

    public function test_title_browser_renders_empty_state_for_unmatched_collections(): void
    {
        Livewire::test(TitleBrowser::class, [
            'types' => [TitleType::Documentary->value],
            'emptyHeading' => 'No documentaries found.',
            'emptyText' => 'Try another route into the catalog.',
        ])
            ->assertSee('No documentaries found.')
            ->assertSee('Try another route into the catalog.');
    }
}
