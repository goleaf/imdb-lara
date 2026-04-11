<?php

namespace Tests\Feature\Feature\Feature\Admin;

use App\Enums\ReportReason;
use App\Enums\ReviewStatus;
use App\Models\Report;
use App\Models\Review;
use App\Models\Title;
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
        $review = $this->createReview([
            'status' => ReviewStatus::Pending->value,
        ]);

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
        $flaggedReview = $this->createReview([
            'headline' => 'Flagged review',
            'status' => ReviewStatus::Published->value,
            'published_at' => now(),
        ]);
        $cleanReview = $this->createReview([
            'headline' => 'Clean review',
            'status' => ReviewStatus::Published->value,
            'published_at' => now(),
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

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createReview(array $attributes = []): Review
    {
        $author = User::factory()->create();
        $title = Title::factory()->movie()->create();

        return Review::withoutEvents(fn (): Review => Review::query()->create(array_merge([
            'user_id' => $author->id,
            'title_id' => $title->getKey(),
            'headline' => 'Moderation queue review',
            'body' => 'Review body for the moderation queue.',
            'contains_spoilers' => false,
            'status' => ReviewStatus::Pending->value,
            'moderated_by' => null,
            'moderated_at' => null,
            'published_at' => null,
            'edited_at' => null,
        ], $attributes)));
    }
}
