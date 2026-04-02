<?php

namespace Tests\Feature\Feature\Admin;

use App\Enums\ContributionStatus;
use App\Enums\ListVisibility;
use App\Enums\ReportStatus;
use App\Enums\ReviewStatus;
use App\Enums\UserStatus;
use App\Models\Contribution;
use App\Models\Report;
use App\Models\Review;
use App\Models\Title;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModerationQueuesTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_can_hide_review_and_suspend_author_from_reports_queue(): void
    {
        $moderator = User::factory()->moderator()->create();
        $author = User::factory()->create();
        $reporter = User::factory()->create();
        $title = Title::factory()->create();

        $review = Review::factory()->published()->for($author, 'author')->for($title)->create();

        $report = Report::factory()->for($reporter, 'reporter')->for($review, 'reportable')->create([
            'status' => ReportStatus::Open,
        ]);

        $this->actingAs($moderator)
            ->patch(route('admin.reports.update', $report), [
                'status' => ReportStatus::Resolved->value,
                'content_action' => 'hide_content',
                'suspend_owner' => true,
                'resolution_notes' => 'Removed and resolved after moderation.',
            ])
            ->assertRedirect(route('admin.reports.index'));

        $review->refresh();
        $author->refresh();
        $report->refresh();
        $this->assertSame(ReviewStatus::Rejected, $review->status);
        $this->assertSame($moderator->id, $review->moderated_by);
        $this->assertNull($review->published_at);
        $this->assertSame(UserStatus::Suspended, $author->status);
        $this->assertSame(ReportStatus::Resolved, $report->status);
        $this->assertSame($moderator->id, $report->reviewed_by);
        $this->assertSame('Removed and resolved after moderation.', $report->resolution_notes);

        $this->assertDatabaseHas('moderation_actions', [
            'report_id' => $report->id,
            'actionable_type' => Review::class,
            'actionable_id' => $review->id,
            'action' => 'hide-content',
        ]);

        $this->assertDatabaseHas('moderation_actions', [
            'report_id' => $report->id,
            'actionable_type' => User::class,
            'actionable_id' => $author->id,
            'action' => 'suspend-user',
        ]);
    }

    public function test_moderator_can_hide_reported_public_list(): void
    {
        $moderator = User::factory()->moderator()->create();
        $owner = User::factory()->create();
        $reporter = User::factory()->create();
        $list = UserList::factory()->public()->for($owner)->create();
        $report = Report::factory()->for($reporter, 'reporter')->for($list, 'reportable')->create();

        $this->actingAs($moderator)
            ->patch(route('admin.reports.update', $report), [
                'status' => ReportStatus::Resolved->value,
                'content_action' => 'hide_content',
                'resolution_notes' => 'List hidden from the public profile.',
            ])
            ->assertRedirect(route('admin.reports.index'));

        $list->refresh();
        $report->refresh();

        $this->assertSame(ListVisibility::Private, $list->visibility);
        $this->assertSame(ReportStatus::Resolved, $report->status);

        $this->assertDatabaseHas('moderation_actions', [
            'report_id' => $report->id,
            'actionable_type' => UserList::class,
            'actionable_id' => $list->id,
            'action' => 'hide-content',
        ]);
    }

    public function test_moderator_can_dismiss_a_report_without_hiding_the_review(): void
    {
        $moderator = User::factory()->moderator()->create();
        $author = User::factory()->create();
        $reporter = User::factory()->create();
        $title = Title::factory()->create();
        $review = Review::factory()->published()->for($author, 'author')->for($title)->create();
        $report = Report::factory()->for($reporter, 'reporter')->for($review, 'reportable')->create([
            'status' => ReportStatus::Open,
        ]);

        $this->actingAs($moderator)
            ->patch(route('admin.reports.update', $report), [
                'status' => ReportStatus::Dismissed->value,
                'content_action' => 'none',
                'resolution_notes' => 'Insufficient evidence to act.',
            ])
            ->assertRedirect(route('admin.reports.index'));

        $review->refresh();
        $report->refresh();

        $this->assertSame(ReviewStatus::Published, $review->status);
        $this->assertSame(ReportStatus::Dismissed, $report->status);
        $this->assertSame('Insufficient evidence to act.', $report->resolution_notes);

        $this->assertDatabaseHas('moderation_actions', [
            'report_id' => $report->id,
            'actionable_type' => Review::class,
            'actionable_id' => $review->id,
            'action' => 'dismiss-report',
        ]);
    }

    public function test_editor_and_moderator_can_review_contributions_queue_but_contributor_cannot_access_it(): void
    {
        $editor = User::factory()->editor()->create();
        $moderator = User::factory()->moderator()->create();
        $contributor = User::factory()->contributor()->create();
        $title = Title::factory()->create();

        $contribution = Contribution::factory()->for($contributor)->for($title, 'contributable')->create([
            'status' => ContributionStatus::Submitted,
        ]);

        $this->actingAs($contributor)
            ->get(route('admin.contributions.index'))
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(route('admin.contributions.index'))
            ->assertOk()
            ->assertSee('Contributions Queue');

        $this->actingAs($moderator)
            ->get(route('admin.contributions.index'))
            ->assertOk()
            ->assertSee('Contributions Queue');

        $this->actingAs($moderator)
            ->patch(route('admin.contributions.update', $contribution), [
                'status' => ContributionStatus::Approved->value,
                'notes' => 'Approved by moderation review.',
            ])
            ->assertRedirect(route('admin.contributions.index'));

        $contribution->refresh();
        $this->assertSame(ContributionStatus::Approved, $contribution->status);
        $this->assertSame($moderator->id, $contribution->reviewed_by);
        $this->assertSame('Approved by moderation review.', $contribution->notes);
        $this->assertNotNull($contribution->reviewed_at);

        $this->assertDatabaseHas('moderation_actions', [
            'report_id' => null,
            'actionable_type' => Contribution::class,
            'actionable_id' => $contribution->id,
            'action' => 'approve-contribution',
        ]);
    }
}
