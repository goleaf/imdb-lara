<?php

namespace Tests\Feature\Feature\Livewire;

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
            ->assertSeeHtml('data-slot="combobox-input"')
            ->assertDontSeeHtml('<select');
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

        $northernSignal = Title::factory()->create(['name' => 'Northern Signal']);
        $mercuryVale = Title::factory()->create(['name' => 'Mercury Vale']);

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
            ->set('genre', '')
            ->set('minimumRating', 8)
            ->assertSee('Northern Signal')
            ->assertDontSee('Mercury Vale');
    }
}
