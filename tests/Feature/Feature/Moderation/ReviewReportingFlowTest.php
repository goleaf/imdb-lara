<?php

namespace Tests\Feature\Feature\Moderation;

use App\Enums\ReportReason;
use App\Livewire\Reviews\ReportReviewForm;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewReportingFlowTest extends TestCase
{
    use RefreshDatabase;

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
}
