<?php

namespace App\Livewire\Titles;

use App\Actions\Titles\DeleteReviewAction;
use App\Actions\Titles\GetUserReviewForTitleAction;
use App\Actions\Titles\StoreReviewAction;
use App\Enums\ReviewStatus;
use App\Livewire\Forms\Titles\ReviewForm;
use App\Models\Review;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ReviewComposer extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public Title $title;

    public string $anchorId = 'title-review';

    public ReviewForm $form;

    public ?string $statusMessage = null;

    #[Locked]
    public ?Review $review = null;

    public function mount(Title $title, GetUserReviewForTitleAction $getUserReviewForTitle): void
    {
        $this->title = $title;

        if (auth()->check()) {
            $this->review = $getUserReviewForTitle->handle(auth()->user(), $title);
        }

        $this->syncForm();
    }

    public function save(StoreReviewAction $storeReview): void
    {
        $this->storeReview($storeReview, ReviewStatus::Pending);
    }

    public function saveDraft(StoreReviewAction $storeReview): void
    {
        $this->storeReview($storeReview, ReviewStatus::Draft);
    }

    public function delete(DeleteReviewAction $deleteReview): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        if (! $this->review) {
            $this->statusMessage = 'There is no review to delete.';

            return;
        }

        $this->authorize('delete', $this->review);

        $deleteReview->handle($this->review);

        $this->review = null;
        $this->form->resetForm();
        $this->statusMessage = 'Review deleted.';
        $this->title->refresh()->load('statistic');
        $this->dispatch('title-review-updated');
    }

    #[Computed]
    public function viewData(): array
    {
        return [
            'reviewSavedAt' => $this->review?->edited_at ?? $this->review?->created_at,
            'reviewStatusBadge' => $this->review?->status
                ? [
                    'color' => $this->review->status->badgeColor(),
                    'label' => $this->review->status->label(),
                ]
                : null,
        ];
    }

    public function render(): View
    {
        return view('livewire.titles.review-composer', $this->viewData);
    }

    private function storeReview(StoreReviewAction $storeReview, ReviewStatus $requestedStatus): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        if ($this->review) {
            $this->authorize('update', $this->review);
        } else {
            $this->authorize('create', Review::class);
        }

        $this->review = $storeReview->handle(
            auth()->user(),
            $this->title,
            $this->form->payload($requestedStatus === ReviewStatus::Draft),
            $requestedStatus,
        );

        $this->statusMessage = match ($this->review->status) {
            ReviewStatus::Draft => 'Draft saved.',
            ReviewStatus::Published => 'Review published.',
            default => 'Review sent to moderation.',
        };

        $this->title->refresh()->load('statistic');
        $this->syncForm();
        $this->dispatch('title-review-updated');
    }

    private function syncForm(): void
    {
        $this->form->fillFromReview($this->review);
    }
}
