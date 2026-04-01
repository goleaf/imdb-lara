<?php

namespace App\Actions\Lists;

use App\Actions\Titles\RefreshTitleStatisticsAction;
use App\Models\Title;
use App\Models\User;

class ToggleWatchlistItemAction
{
    public function __construct(
        public EnsureWatchlistAction $ensureWatchlist,
        public RefreshTitleStatisticsAction $refreshTitleStatistics,
    ) {}

    public function handle(User $user, Title $title): bool
    {
        $watchlist = $this->ensureWatchlist->handle($user);
        $existingItem = $watchlist->items()->where('title_id', $title->id)->first();

        if ($existingItem) {
            $existingItem->delete();

            $this->refreshTitleStatistics->handle($title);

            return false;
        }

        $watchlist->items()->create([
            'title_id' => $title->id,
            'position' => (int) $watchlist->items()->max('position') + 1,
        ]);

        $this->refreshTitleStatistics->handle($title);

        return true;
    }
}
