<?php

namespace Tests\Feature\Feature\Foundations;

use App\Models\ListItem;
use App\Models\Rating;
use App\Models\Review;
use App\Models\Title;
use App\Models\TitleStatistic;
use App\Models\UserList;
use App\ReviewStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TitleStatisticObserversTest extends TestCase
{
    use RefreshDatabase;

    public function test_rating_events_keep_title_statistics_in_sync(): void
    {
        $title = Title::factory()->create();

        $firstRating = Rating::factory()->for($title)->create(['score' => 8]);

        $this->assertTitleStatistics($title, 1, '8.00', 0, 0);

        $secondRating = Rating::factory()->for($title)->create(['score' => 6]);

        $this->assertTitleStatistics($title, 2, '7.00', 0, 0);

        $firstRating->update(['score' => 10]);

        $this->assertTitleStatistics($title, 2, '8.00', 0, 0);

        $secondRating->delete();

        $this->assertTitleStatistics($title, 1, '10.00', 0, 0);
    }

    public function test_review_events_only_count_published_reviews(): void
    {
        $title = Title::factory()->create();
        $pendingReview = Review::factory()->for($title)->create([
            'status' => ReviewStatus::Pending,
        ]);

        $this->assertTitleStatistics($title, 0, '0.00', 0, 0);

        $pendingReview->update([
            'status' => ReviewStatus::Published,
            'published_at' => now(),
        ]);

        $this->assertTitleStatistics($title, 0, '0.00', 1, 0);

        $publishedReview = Review::factory()->for($title)->published()->create();

        $this->assertTitleStatistics($title, 0, '0.00', 2, 0);

        $publishedReview->delete();

        $this->assertTitleStatistics($title, 0, '0.00', 1, 0);
    }

    public function test_watchlist_item_events_keep_watchlist_counts_current(): void
    {
        $title = Title::factory()->create();
        $customList = UserList::factory()->create();

        ListItem::factory()->for($title)->for($customList)->create();

        $this->assertNull($title->statistic()->first());

        $watchlist = UserList::factory()->watchlist()->create();
        $watchlistItem = ListItem::factory()->for($title)->for($watchlist)->create();

        $this->assertTitleStatistics($title, 0, '0.00', 0, 1);

        $watchlistItem->delete();

        $this->assertTitleStatistics($title, 0, '0.00', 0, 0);
    }

    private function assertTitleStatistics(
        Title $title,
        int $ratingCount,
        string $averageRating,
        int $reviewCount,
        int $watchlistCount,
    ): void {
        $statistic = TitleStatistic::query()
            ->select([
                'id',
                'title_id',
                'rating_count',
                'average_rating',
                'review_count',
                'watchlist_count',
            ])
            ->whereBelongsTo($title)
            ->first();

        $this->assertNotNull($statistic);
        $this->assertSame($ratingCount, $statistic->rating_count);
        $this->assertSame($averageRating, $statistic->average_rating);
        $this->assertSame($reviewCount, $statistic->review_count);
        $this->assertSame($watchlistCount, $statistic->watchlist_count);
    }
}
