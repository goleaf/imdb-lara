<?php

namespace App\Livewire\Admin;

use App\Actions\Moderation\ModerateReviewAction;
use App\Enums\ReportStatus;
use App\Enums\ReviewStatus;
use App\Models\Review;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ReviewModerationCard extends Component
{
    use AuthorizesRequests;

    public ?string $moderationNotes = null;

    #[Locked]
    public Review $review;

    public string $status = ReviewStatus::Pending->value;

    public ?string $statusMessage = null;

    public function mount(Review $review): void
    {
        $this->authorize('moderate', $review);

        $this->review = $review;
        $this->refreshReviewState();
        $this->status = $this->review->status->value;
    }

    public function save(ModerateReviewAction $moderateReview): void
    {
        $this->authorize('moderate', $this->review);

        $validated = $this->validate([
            'status' => ['required', Rule::enum(ReviewStatus::class)],
            'moderationNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->review = $moderateReview
            ->handle(
                auth()->user(),
                $this->review,
                ReviewStatus::from($validated['status']),
                $validated['moderationNotes'],
            );

        $this->refreshReviewState();
        $this->status = $this->review->status->value;
        $this->moderationNotes = null;
        $this->statusMessage = 'Review moderation saved.';

        $this->dispatch('moderation-queue-updated');
    }

    public function render()
    {
        $this->refreshReviewState();

        return view('livewire.admin.review-moderation-card', [
            'reviewStatuses' => ReviewStatus::cases(),
        ]);
    }

    private function refreshReviewState(): void
    {
        $this->review = $this->review
            ->loadMissing(Review::adminQueueRelations())
            ->loadCount([
                'votes as helpful_votes_count' => fn ($voteQuery) => $voteQuery->where('is_helpful', true),
                'reports as open_reports_count' => fn ($reportQuery) => $reportQuery->where('status', ReportStatus::Open),
            ]);
    }
}
