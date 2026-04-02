<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminReviewsIndexQueryAction;
use App\Enums\ReviewStatus;
use App\Livewire\Pages\Concerns\RendersPageView;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ReviewsPage extends Component
{
    use RendersPageView;
    use WithPagination;

    public function render(BuildAdminReviewsIndexQueryAction $buildAdminReviewsIndexQuery): View
    {
        $reviewFilters = [
            'status' => request()->string('status')->toString() ?: 'pending',
            'sort' => request()->string('sort')->toString() ?: 'flagged',
            'flaggedOnly' => request()->boolean('flaggedOnly'),
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
