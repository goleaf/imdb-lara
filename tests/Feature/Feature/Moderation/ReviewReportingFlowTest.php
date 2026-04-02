<?php

namespace Tests\Feature\Feature\Moderation;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Livewire\Reviews\ReportReviewForm;
use App\Models\Report;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewReportingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_review_form_renders_a_combobox_for_reason_selection(): void
    {
        $review = Review::factory()->published()->create();

        Livewire::test(ReportReviewForm::class, ['review' => $review])
            ->assertSeeHtml('data-slot="alert"')
            ->assertSeeHtml('data-slot="alert-description"')
            ->assertSeeHtml('data-slot="alert-controls"')
            ->assertSeeHtml('data-slot="combobox-input"')
            ->assertDontSeeHtml('<select');
    }

    public function test_guest_is_redirected_when_trying_to_report_a_review(): void
    {
        $review = Review::factory()->published()->create();

        Livewire::test(ReportReviewForm::class, ['review' => $review])
            ->set('form.reason', ReportReason::Spoiler->value)
            ->set('form.details', 'Contains an unmarked ending reveal.')
            ->call('save')
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_report_a_review_for_moderation(): void
    {
        $review = Review::factory()->published()->create([
            'headline' => 'Elegant and sharp.',
        ]);
        $reporter = User::factory()->create();
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($reporter)
            ->test(ReportReviewForm::class, ['review' => $review])
            ->set('form.reason', ReportReason::Spoiler->value)
            ->set('form.details', 'Contains an unmarked ending reveal.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('reports', [
            'user_id' => $reporter->id,
            'reportable_type' => Review::class,
            'reportable_id' => $review->id,
            'reason' => ReportReason::Spoiler->value,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Reports')
            ->assertSee('Spoiler');
    }

    public function test_reporting_a_review_again_reopens_the_existing_report_record(): void
    {
        $review = Review::factory()->published()->create();
        $reporter = User::factory()->create();
        $moderator = User::factory()->moderator()->create();

        $report = Report::factory()->for($reporter, 'reporter')->for($review, 'reportable')->create([
            'reason' => ReportReason::Spam,
            'status' => ReportStatus::Dismissed,
            'reviewed_by' => $moderator->id,
            'reviewed_at' => now(),
            'resolution_notes' => 'Previously dismissed.',
        ]);

        Livewire::actingAs($reporter)
            ->test(ReportReviewForm::class, ['review' => $review])
            ->set('form.reason', ReportReason::Spoiler->value)
            ->set('form.details', 'The ending is revealed outright.')
            ->call('save')
            ->assertHasNoErrors();

        $report->refresh();

        $this->assertSame(ReportReason::Spoiler, $report->reason);
        $this->assertSame('The ending is revealed outright.', $report->details);
        $this->assertSame(ReportStatus::Open, $report->status);
        $this->assertNull($report->reviewed_by);
        $this->assertNull($report->reviewed_at);
        $this->assertNull($report->resolution_notes);
    }
}
