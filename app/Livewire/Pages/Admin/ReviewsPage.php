<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminReviewsIndexQueryAction;
use App\Enums\ReviewStatus;
use App\Livewire\Pages\Concerns\RendersPageView;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ReviewsPage extends Component
{
    use RendersPageView;
    use WithPagination;

    #[Url]
    public bool $flaggedOnly = false;

    #[Url]
    public string $sort = 'flagged';

    #[Url]
    public string $status = 'pending';

    public function mount(): void
    {
        if (! in_array($this->status, ['all', ...array_map(
            static fn (ReviewStatus $reviewStatus): string => $reviewStatus->value,
            ReviewStatus::cases(),
        )], true)) {
            $this->status = 'pending';
        }

        if (! in_array($this->sort, ['flagged', 'helpful', 'oldest'], true)) {
            $this->sort = 'flagged';
        }
    }

    #[On('moderation-queue-updated')]
    public function refreshQueue(): void
    {
        // Re-render the page so Livewire queue filters and ordering stay in sync.
    }

    public function resetFilters(): void
    {
        $this->status = 'pending';
        $this->sort = 'flagged';
        $this->flaggedOnly = false;
        $this->resetPage();
    }

    public function updatedFlaggedOnly(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        if (! in_array($this->sort, ['flagged', 'helpful', 'oldest'], true)) {
            $this->sort = 'flagged';
        }

        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        if (! in_array($this->status, ['all', ...array_map(
            static fn (ReviewStatus $reviewStatus): string => $reviewStatus->value,
            ReviewStatus::cases(),
        )], true)) {
            $this->status = 'pending';
        }

        $this->resetPage();
    }

    public function render(BuildAdminReviewsIndexQueryAction $buildAdminReviewsIndexQuery): View
    {
        $reviewFilters = [
            'status' => $this->status,
            'sort' => $this->sort,
            'flaggedOnly' => $this->flaggedOnly,
        ];

        return $this->renderPageView('admin.reviews.index', [
            'reviews' => $buildAdminReviewsIndexQuery
                ->handle($reviewFilters)
                ->simplePaginate(20)
                ->withQueryString(),
            'reviewStatuses' => ReviewStatus::cases(),
            'reviewFilters' => $reviewFilters,
        ]);
    }
}
