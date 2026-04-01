<?php

namespace App\Observers;

use App\Actions\Titles\RefreshTitleStatisticsAction;
use App\Models\Review;
use App\Models\Title;

class ReviewObserver
{
    public function __construct(
        public RefreshTitleStatisticsAction $refreshTitleStatistics,
    ) {}

    public function created(Review $review): void
    {
        $this->refreshStatistics($review);
    }

    public function updated(Review $review): void
    {
        $this->refreshStatistics($review);
    }

    public function deleted(Review $review): void
    {
        $this->refreshStatistics($review);
    }

    public function restored(Review $review): void
    {
        $this->refreshStatistics($review);
    }

    public function forceDeleted(Review $review): void
    {
        $this->refreshStatistics($review);
    }

    private function refreshStatistics(Review $review): void
    {
        $this->refreshTitles([
            (int) $review->title_id,
            (int) $review->getOriginal('title_id'),
        ]);
    }

    /**
     * @param  list<int>  $titleIds
     */
    private function refreshTitles(array $titleIds): void
    {
        $uniqueTitleIds = array_values(array_unique(array_filter($titleIds)));

        if ($uniqueTitleIds === []) {
            return;
        }

        Title::query()
            ->select(['id'])
            ->whereKey($uniqueTitleIds)
            ->get()
            ->each(fn (Title $title) => $this->refreshTitleStatistics->handle($title));
    }
}
