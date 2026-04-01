<?php

namespace App\Observers;

use App\Actions\Titles\RefreshTitleStatisticsAction;
use App\Models\Rating;
use App\Models\Title;

class RatingObserver
{
    public function __construct(
        public RefreshTitleStatisticsAction $refreshTitleStatistics,
    ) {}

    public function created(Rating $rating): void
    {
        $this->refreshStatistics($rating);
    }

    public function updated(Rating $rating): void
    {
        $this->refreshStatistics($rating);
    }

    public function deleted(Rating $rating): void
    {
        $this->refreshStatistics($rating);
    }

    public function restored(Rating $rating): void
    {
        $this->refreshStatistics($rating);
    }

    public function forceDeleted(Rating $rating): void
    {
        $this->refreshStatistics($rating);
    }

    private function refreshStatistics(Rating $rating): void
    {
        $this->refreshTitles([
            (int) $rating->title_id,
            (int) $rating->getOriginal('title_id'),
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
