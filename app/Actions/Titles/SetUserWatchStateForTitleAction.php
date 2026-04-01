<?php

namespace App\Actions\Titles;

use App\Actions\Lists\EnsureWatchlistAction;
use App\Enums\WatchState;
use App\Models\ListItem;
use App\Models\Title;
use App\Models\User;

class SetUserWatchStateForTitleAction
{
    public function __construct(
        public EnsureWatchlistAction $ensureWatchlist,
    ) {}

    public function handle(User $user, Title $title, WatchState $watchState): ListItem
    {
        $watchlist = $this->ensureWatchlist->handle($user);
        $watchlistEntry = $watchlist->items()->firstOrNew([
            'title_id' => $title->id,
        ]);

        if (! $watchlistEntry->exists) {
            $watchlistEntry->position = (int) $watchlist->items()->max('position') + 1;
        }

        $watchlistEntry->watch_state = $watchState;

        match ($watchState) {
            WatchState::Planned => $this->markPlanned($watchlistEntry),
            WatchState::Watching => $this->markWatching($watchlistEntry),
            WatchState::Completed => $this->markCompleted($watchlistEntry),
            WatchState::Paused, WatchState::Dropped => $this->markPausedOrDropped($watchlistEntry),
        };

        $watchlistEntry->save();

        return $watchlistEntry->refresh();
    }

    private function markPlanned(ListItem $watchlistEntry): void
    {
        $watchlistEntry->started_at = null;
        $watchlistEntry->watched_at = null;
    }

    private function markWatching(ListItem $watchlistEntry): void
    {
        $watchlistEntry->started_at ??= now();
        $watchlistEntry->watched_at = null;
    }

    private function markCompleted(ListItem $watchlistEntry): void
    {
        $watchlistEntry->started_at ??= now();
        $watchlistEntry->watched_at = now();
    }

    private function markPausedOrDropped(ListItem $watchlistEntry): void
    {
        $watchlistEntry->started_at ??= now();
        $watchlistEntry->watched_at = null;
    }
}
