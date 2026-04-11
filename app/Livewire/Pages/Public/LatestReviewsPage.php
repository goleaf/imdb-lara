<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Home\GetLatestReviewFeedAction;
use App\Actions\Seo\PageSeoData;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\Review;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class LatestReviewsPage extends Component
{
    use RendersPageView;

    public function render(GetLatestReviewFeedAction $getLatestReviewFeed): View
    {
        $reviews = $getLatestReviewFeed
            ->query()
            ->simplePaginate(12)
            ->withQueryString();
        $reviews->setCollection(
            $reviews->getCollection()
                ->filter(fn (Review $review): bool => $review->title !== null)
                ->values(),
        );

        return $this->renderPageView('reviews.index', [
            'reviews' => $reviews,
            'seo' => new PageSeoData(
                title: 'Latest Reviews',
                description: 'Browse the latest published audience reviews across public Screenbase title pages.',
                canonical: route('public.reviews.latest'),
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Latest Reviews'],
                ],
                paginationPageName: 'page',
            ),
        ]);
    }
}
