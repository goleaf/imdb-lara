<?php

namespace App\Livewire\Reviews;

use App\Actions\Moderation\ReportReviewAction;
use App\Enums\ReportReason;
use App\Models\Review;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ReportReviewForm extends Component
{
    public Review $review;

    public string $reason = 'spoiler';

    public string $details = '';

    public ?string $statusMessage = null;

    protected function rules(): array
    {
        return [
            'reason' => ['required', Rule::in(array_map(fn (ReportReason $reason): string => $reason->value, ReportReason::cases()))],
            'details' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function mount(Review $review): void
    {
        $this->review = $review;
    }

    public function save(ReportReviewAction $reportReview): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $validated = $this->validate();

        $reportReview->handle(auth()->user(), $this->review, $validated);

        $this->statusMessage = 'Review reported.';
        $this->details = '';
    }

    public function render()
    {
        return view('livewire.reviews.report-review-form');
    }
}
