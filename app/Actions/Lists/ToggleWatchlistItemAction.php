<?php

namespace App\Actions\Lists;

use App\Models\Title;
use App\Models\User;
use App\WatchState;

class ToggleWatchlistItemAction
{
    public function __construct(
        public EnsureWatchlistAction $ensureWatchlist,
    ) {}

    public function handle(User $user, Title $title): bool
    {
        $watchlist = $this->ensureWatchlist->handle($user);
        $existingItem = $watchlist->items()->where('title_id', $title->id)->first();

        if ($existingItem) {
            $existingItem->delete();

            return false;
        }

        $watchlist->items()->create([
            'title_id' => $title->id,
            'position' => (int) $watchlist->items()->max('position') + 1,
            'watch_state' => WatchState::Planned,
        ]);

        return true;
    }
}
