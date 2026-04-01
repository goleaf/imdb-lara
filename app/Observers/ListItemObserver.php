<?php

namespace App\Observers;

use App\Actions\Titles\RefreshTitleStatisticsAction;
use App\Models\ListItem;
use App\Models\Title;
use App\Models\UserList;

class ListItemObserver
{
    public function __construct(
        public RefreshTitleStatisticsAction $refreshTitleStatistics,
    ) {}

    public function created(ListItem $listItem): void
    {
        $this->refreshStatistics($listItem);
    }

    public function updated(ListItem $listItem): void
    {
        $this->refreshStatistics($listItem);
    }

    public function deleted(ListItem $listItem): void
    {
        $this->refreshStatistics($listItem);
    }

    public function restored(ListItem $listItem): void
    {
        $this->refreshStatistics($listItem);
    }

    public function forceDeleted(ListItem $listItem): void
    {
        $this->refreshStatistics($listItem);
    }

    private function refreshStatistics(ListItem $listItem): void
    {
        $listIds = array_values(array_unique(array_filter([
            (int) $listItem->user_list_id,
            (int) $listItem->getOriginal('user_list_id'),
        ])));

        if ($listIds === []) {
            return;
        }

        $watchlistLookup = UserList::query()
            ->select(['id'])
            ->whereKey($listIds)
            ->where('is_watchlist', true)
            ->pluck('id')
            ->mapWithKeys(fn ($listId): array => [(int) $listId => true])
            ->all();

        $titleIds = [];
        $currentListId = (int) $listItem->user_list_id;
        $originalListId = (int) $listItem->getOriginal('user_list_id');

        if (isset($watchlistLookup[$currentListId])) {
            $titleIds[] = (int) $listItem->title_id;
        }

        if (isset($watchlistLookup[$originalListId])) {
            $titleIds[] = (int) $listItem->getOriginal('title_id');
        }

        $this->refreshTitles($titleIds);
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
