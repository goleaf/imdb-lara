<?php

namespace Tests\Feature\Feature\Admin;

use App\Enums\ContributionAction;
use App\Enums\ContributionStatus;
use App\Enums\ListVisibility;
use App\Enums\ReportStatus;
use App\Enums\ReviewStatus;
use App\Enums\UserStatus;
use App\Livewire\Admin\ContributionModerationCard;
use App\Livewire\Admin\ReportModerationCard;
use App\Livewire\Admin\ReviewModerationCard;
use App\Models\Contribution;
use App\Models\Report;
use App\Models\Review;
use App\Models\Title;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class AdminModerationQueuesTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use RefreshDatabase;
    use UsesCatalogOnlyApplication;

    public function test_moderator_can_moderate_a_review_from_the_livewire_queue_card(): void
    {
        $moderator = User::factory()->moderator()->create();
        $author = User::factory()->create();
        $title = $this->sampleTitle();
        $review = $this->createReview($author, $title, [
            'status' => ReviewStatus::Published->value,
            'published_at' => now(),
        ]);

        Livewire::actingAs($moderator)
            ->test(ReviewModerationCard::class, ['review' => $review])
            ->set('status', ReviewStatus::Rejected->value)
            ->set('moderationNotes', 'Escalated by moderation queue.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('status', ReviewStatus::Rejected->value)
            ->assertSet('statusMessage', 'Review moderation saved.')
            ->assertDispatched('moderation-queue-updated');

        $review->refresh();

        $this->assertSame(ReviewStatus::Rejected, $review->status);
        $this->assertSame($moderator->id, $review->moderated_by);
        $this->assertNull($review->published_at);

        $this->assertDatabaseHas('moderation_actions', [
            'actionable_type' => Review::class,
            'actionable_id' => $review->id,
            'action' => 'reject',
            'notes' => 'Escalated by moderation queue.',
        ]);
    }

    public function test_moderator_can_hide_review_and_suspend_author_from_reports_queue(): void
    {
        $moderator = User::factory()->moderator()->create();
        $author = User::factory()->create();
        $reporter = User::factory()->create();
        $title = $this->sampleTitle();

        $review = $this->createReview($author, $title, [
            'status' => ReviewStatus::Published->value,
            'published_at' => now(),
        ]);

        $report = Report::factory()->for($reporter, 'reporter')->for($review, 'reportable')->create([
            'status' => ReportStatus::Open,
        ]);

        Livewire::actingAs($moderator)
            ->test(ReportModerationCard::class, ['report' => $report])
            ->set('status', ReportStatus::Resolved->value)
            ->set('contentAction', 'hide_content')
            ->set('suspendOwner', true)
            ->set('resolutionNotes', 'Removed and resolved after moderation.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('status', ReportStatus::Resolved->value)
            ->assertSet('statusMessage', 'Report moderation saved.')
            ->assertDispatched('moderation-queue-updated');

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

    public function test_moderator_can_resolve_a_report_from_the_livewire_queue_card(): void
    {
        $moderator = User::factory()->moderator()->create();
        $author = User::factory()->create();
        $reporter = User::factory()->create();
        $title = $this->sampleTitle();
        $review = $this->createReview($author, $title, [
            'status' => ReviewStatus::Published->value,
            'published_at' => now(),
        ]);
        $report = Report::factory()->for($reporter, 'reporter')->for($review, 'reportable')->create([
            'status' => ReportStatus::Open,
        ]);

        Livewire::actingAs($moderator)
            ->test(ReportModerationCard::class, ['report' => $report])
            ->set('status', ReportStatus::Resolved->value)
            ->set('contentAction', 'hide_content')
            ->set('suspendOwner', true)
            ->set('resolutionNotes', 'Resolved from Livewire moderation card.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('status', ReportStatus::Resolved->value)
            ->assertSet('statusMessage', 'Report moderation saved.')
            ->assertDispatched('moderation-queue-updated');

        $review->refresh();
        $report->refresh();
        $author->refresh();

        $this->assertSame(ReviewStatus::Rejected, $review->status);
        $this->assertSame(ReportStatus::Resolved, $report->status);
        $this->assertSame($moderator->id, $report->reviewed_by);
        $this->assertSame('Resolved from Livewire moderation card.', $report->resolution_notes);
        $this->assertSame(UserStatus::Suspended, $author->status);
    }

    public function test_moderator_can_hide_reported_public_list(): void
    {
        $moderator = User::factory()->moderator()->create();
        $owner = User::factory()->create();
        $reporter = User::factory()->create();
        $list = UserList::factory()->public()->for($owner)->create();
        $report = Report::factory()->for($reporter, 'reporter')->for($list, 'reportable')->create();

        Livewire::actingAs($moderator)
            ->test(ReportModerationCard::class, ['report' => $report])
            ->set('status', ReportStatus::Resolved->value)
            ->set('contentAction', 'hide_content')
            ->set('resolutionNotes', 'List hidden from the public profile.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('status', ReportStatus::Resolved->value)
            ->assertSet('statusMessage', 'Report moderation saved.')
            ->assertDispatched('moderation-queue-updated');

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
        $title = $this->sampleTitle();
        $review = $this->createReview($author, $title, [
            'status' => ReviewStatus::Published->value,
            'published_at' => now(),
        ]);
        $report = Report::factory()->for($reporter, 'reporter')->for($review, 'reportable')->create([
            'status' => ReportStatus::Open,
        ]);

        Livewire::actingAs($moderator)
            ->test(ReportModerationCard::class, ['report' => $report])
            ->set('status', ReportStatus::Dismissed->value)
            ->set('contentAction', 'none')
            ->set('resolutionNotes', 'Insufficient evidence to act.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('status', ReportStatus::Dismissed->value)
            ->assertSet('statusMessage', 'Report moderation saved.')
            ->assertDispatched('moderation-queue-updated');

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
        $title = $this->sampleTitle();

        $contribution = $this->createContribution($contributor, $title, [
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

        Livewire::actingAs($moderator)
            ->test(ContributionModerationCard::class, ['contribution' => $contribution])
            ->set('status', ContributionStatus::Approved->value)
            ->set('notes', 'Approved by moderation review.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('status', ContributionStatus::Approved->value)
            ->assertSet('statusMessage', 'Contribution review saved.')
            ->assertDispatched('moderation-queue-updated');

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

    public function test_editor_can_review_a_contribution_from_the_livewire_queue_card(): void
    {
        $editor = User::factory()->editor()->create();
        $contributor = User::factory()->contributor()->create();
        $title = $this->sampleTitle();

        $contribution = $this->createContribution($contributor, $title, [
            'status' => ContributionStatus::Submitted,
        ]);

        Livewire::actingAs($editor)
            ->test(ContributionModerationCard::class, ['contribution' => $contribution])
            ->set('status', ContributionStatus::Approved->value)
            ->set('notes', 'Approved from the Livewire moderation queue.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('status', ContributionStatus::Approved->value)
            ->assertSet('statusMessage', 'Contribution review saved.')
            ->assertDispatched('moderation-queue-updated');

        $contribution->refresh();

        $this->assertSame(ContributionStatus::Approved, $contribution->status);
        $this->assertSame($editor->id, $contribution->reviewed_by);
        $this->assertSame('Approved from the Livewire moderation queue.', $contribution->notes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createContribution(User $contributor, Title $title, array $attributes = []): Contribution
    {
        $this->seedLocalTitleRecord($title);

        return Contribution::query()->create(array_merge([
            'user_id' => $contributor->id,
            'contributable_type' => Title::class,
            'contributable_id' => $title->getKey(),
            'action' => ContributionAction::Update->value,
            'status' => ContributionStatus::Submitted->value,
            'payload' => [
                'field' => 'plot_outline',
                'value' => 'Suggested catalog update.',
            ],
            'notes' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createReview(User $author, Title $title, array $attributes = []): Review
    {
        $this->seedLocalTitleRecord($title);

        return Review::withoutEvents(fn (): Review => Review::query()->create(array_merge([
            'user_id' => $author->id,
            'title_id' => $title->getKey(),
            'headline' => 'Moderation queue review',
            'body' => 'A review body used to exercise the Livewire moderation queue.',
            'contains_spoilers' => false,
            'status' => ReviewStatus::Pending->value,
            'moderated_by' => null,
            'moderated_at' => null,
            'published_at' => null,
            'edited_at' => null,
        ], $attributes)));
    }

    private function seedLocalTitleRecord(Title $title): void
    {
        $attributes = $title->getAttributes();

        DB::table('titles')->updateOrInsert(
            ['id' => $title->getKey()],
            [
                'name' => (string) ($attributes['primarytitle'] ?? 'Catalog Title '.$title->getKey()),
                'original_name' => $attributes['originaltitle'] ?? null,
                'slug' => 'catalog-title-'.$title->getKey(),
                'title_type' => 'movie',
                'release_year' => $attributes['startyear'] ?? null,
                'end_year' => $attributes['endyear'] ?? null,
                'release_date' => null,
                'runtime_minutes' => $attributes['runtimeminutes'] ?? null,
                'age_rating' => null,
                'plot_outline' => null,
                'synopsis' => null,
                'tagline' => null,
                'origin_country' => null,
                'original_language' => null,
                'popularity_rank' => null,
                'is_published' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
}
