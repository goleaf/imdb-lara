<?php

namespace Tests\Unit\Actions;

use App\Actions\Search\GetDiscoveryTitleSuggestionsAction;
use App\Enums\TitleType;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetDiscoveryTitleSuggestionsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_published_non_episode_matches_sorted_by_popularity_rank(): void
    {
        Title::factory()->create([
            'name' => 'Northern Signal',
            'search_keywords' => 'signal, sci-fi',
            'title_type' => TitleType::Movie,
            'popularity_rank' => 12,
            'is_published' => true,
        ]);
        Title::factory()->create([
            'name' => 'Signal North',
            'search_keywords' => 'signal, thriller',
            'title_type' => TitleType::Series,
            'popularity_rank' => 4,
            'is_published' => true,
        ]);
        Title::factory()->create([
            'name' => 'Signal Hidden',
            'search_keywords' => 'signal, unreleased',
            'title_type' => TitleType::Movie,
            'popularity_rank' => 1,
            'is_published' => false,
        ]);
        Title::factory()->create([
            'name' => 'Signal Episode',
            'search_keywords' => 'signal, episode',
            'title_type' => TitleType::Episode,
            'popularity_rank' => 2,
            'is_published' => true,
        ]);

        $suggestions = app(GetDiscoveryTitleSuggestionsAction::class)->handle('Signal');

        $this->assertSame([
            'Signal North',
            'Northern Signal',
        ], $suggestions->pluck('name')->all());
    }

    public function test_it_requires_a_meaningful_search_term(): void
    {
        Title::factory()->create([
            'name' => 'Northern Signal',
            'search_keywords' => 'signal, sci-fi',
            'is_published' => true,
        ]);

        $suggestions = app(GetDiscoveryTitleSuggestionsAction::class)->handle('s');

        $this->assertTrue($suggestions->isEmpty());
    }
}
