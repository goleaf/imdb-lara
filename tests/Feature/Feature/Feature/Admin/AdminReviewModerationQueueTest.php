<?php

namespace Tests\Feature\Feature\Feature\Admin;

use App\Enums\ReportReason;
use App\Models\Report;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReviewModerationQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_and_reports_queues_are_limited_to_moderation_roles(): void
    {
        $moderator = User::factory()->moderator()->create();
        $editor = User::factory()->editor()->create();
        $review = Review::factory()->create();

        $this->actingAs($moderator)
            ->get(route('admin.reviews.index'))
            ->assertOk()
            ->assertSee('Moderate Reviews')
            ->assertSee($review->headline ?: 'Untitled review');

        $this->actingAs($moderator)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Reports');

        $this->actingAs($editor)
            ->get(route('admin.reviews.index'))
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(route('admin.reports.index'))
            ->assertForbidden();
    }

    public function test_moderator_can_filter_the_review_queue_to_flagged_reviews_only(): void
    {
        $moderator = User::factory()->moderator()->create();
        $flaggedReview = Review::factory()->published()->create([
            'headline' => 'Flagged review',
        ]);
        $cleanReview = Review::factory()->published()->create([
            'headline' => 'Clean review',
        ]);

        Report::factory()
            ->for(User::factory(), 'reporter')
            ->for($flaggedReview, 'reportable')
            ->create([
                'reason' => ReportReason::Spoiler,
            ]);

        $this->actingAs($moderator)
            ->get(route('admin.reviews.index', [
                'status' => 'all',
                'sort' => 'flagged',
                'flaggedOnly' => 1,
            ]))
            ->assertOk()
            ->assertSee('Flagged review')
            ->assertDontSee('Clean review')
            ->assertSee('1 open reports');
    }
}
