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
            ->select([
                'list_items.id',
                'list_items.title_id',
                'list_items.watch_state',
                'list_items.started_at',
                'list_items.watched_at',
            ])
            ->where('list_items.title_id', $title->id)
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
