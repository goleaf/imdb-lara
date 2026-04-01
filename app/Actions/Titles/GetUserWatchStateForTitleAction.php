<?php

namespace App\Actions\Titles;

use App\Enums\WatchState;
use App\Models\Title;
use App\Models\User;
use Carbon\CarbonInterface;

class GetUserWatchStateForTitleAction
{
    /**
     * @return array{state: WatchState, started_at: CarbonInterface|null, watched_at: CarbonInterface|null}|null
     */
    public function handle(User $user, Title $title): ?array
    {
        $watchlistEntry = $user->watchlistEntries()
            ->select(['id', 'title_id', 'watch_state', 'started_at', 'watched_at'])
            ->where('title_id', $title->id)
            ->first();

        if (! $watchlistEntry?->watch_state instanceof WatchState) {
            return null;
        }

        return [
            'state' => $watchlistEntry->watch_state,
            'started_at' => $watchlistEntry->started_at,
            'watched_at' => $watchlistEntry->watched_at,
        ];
    }
}
