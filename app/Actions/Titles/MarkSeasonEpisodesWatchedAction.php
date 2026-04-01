<?php

namespace App\Actions\Titles;

use App\Actions\Lists\EnsureWatchlistAction;
use App\Enums\WatchState;
use App\Models\Season;
use App\Models\User;

class MarkSeasonEpisodesWatchedAction
{
    public function __construct(
        public EnsureWatchlistAction $ensureWatchlist,
    ) {}

    public function handle(User $user, Season $season): int
    {
        $episodeTitleIds = $season->episodes()
            ->select(['title_id'])
            ->pluck('title_id')
            ->filter()
            ->values();

        if ($episodeTitleIds->isEmpty()) {
            return 0;
        }

        $watchlist = $this->ensureWatchlist->handle($user);
        $existingEntries = $watchlist->items()
            ->whereIn('title_id', $episodeTitleIds)
            ->get()
            ->keyBy('title_id');

        $nextPosition = (int) $watchlist->items()->max('position') + 1;
        $timestamp = now();

        foreach ($episodeTitleIds as $titleId) {
            $watchlistEntry = $existingEntries->get($titleId)
                ?? $watchlist->items()->make([
                    'title_id' => $titleId,
                    'position' => $nextPosition++,
                ]);

            $watchlistEntry->watch_state = WatchState::Completed;
            $watchlistEntry->started_at ??= $timestamp;
            $watchlistEntry->watched_at = $timestamp;
            $watchlistEntry->save();
        }

        return $episodeTitleIds->count();
    }
}
