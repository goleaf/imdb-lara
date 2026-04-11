<?php

namespace App\Livewire\Reviews;

use App\Actions\Moderation\ReportReviewAction;
use App\Enums\ReportReason;
use App\Livewire\Forms\Reviews\ReportReviewForm as ReportReviewDataForm;
use App\Models\Review;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ReportReviewForm extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public Review $review;

    public ReportReviewDataForm $form;

    public ?string $statusMessage = null;

    public string $modalId = '';

    /**
     * @var list<array{value: string, label: string, icon: string}>
     */
    public array $reportReasons = [];

    public function mount(Review $review): void
    {
        $this->review = $review;
        $this->modalId = 'report-review-'.$review->id;
        $this->form->reason = ReportReason::Spoiler->value;
        $this->reportReasons = array_map(
            static fn (ReportReason $reportReason): array => [
                'value' => $reportReason->value,
                'label' => $reportReason->label(),
                'icon' => $reportReason->icon(),
            ],
            ReportReason::cases(),
        );
    }

    public function save(ReportReviewAction $reportReview): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $this->authorize('report', $this->review);
        $reportReview->handle(auth()->user(), $this->review, $this->form->payload());

        $this->statusMessage = 'Review reported.';
        $this->form->reset('details');
    }

    public function render()
    {
        return view('livewire.reviews.report-review-form');
    }
}
