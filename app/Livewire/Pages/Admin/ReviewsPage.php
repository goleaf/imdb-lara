<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminReviewsIndexQueryAction;
use App\Enums\ReviewStatus;
use App\Livewire\Pages\Concerns\RendersLegacyPage;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ReviewsPage extends Component
{
    use RendersLegacyPage;
    use WithPagination;

    public function render(BuildAdminReviewsIndexQueryAction $buildAdminReviewsIndexQuery): View
    {
        return $this->renderLegacyPage('admin.reviews.index', [
            'reviews' => $buildAdminReviewsIndexQuery
                ->handle()
                ->simplePaginate(20)
                ->withQueryString(),
            'reviewStatuses' => ReviewStatus::cases(),
        ]);
    }
}
