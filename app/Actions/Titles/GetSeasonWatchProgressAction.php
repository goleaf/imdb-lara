<?php

namespace App\Actions\Titles;

use App\Enums\WatchState;
use App\Models\Season;
use App\Models\User;

class GetSeasonWatchProgressAction
{
    /**
     * @return array{total: int, watched: int, remaining: int, percentage: int}
     */
    public function handle(User $user, Season $season): array
    {
        $episodeTitleIds = $season->episodes()
            ->select(['title_id'])
            ->pluck('title_id')
            ->filter()
            ->values();

        $total = $episodeTitleIds->count();

        if ($total === 0) {
            return [
                'total' => 0,
                'watched' => 0,
                'remaining' => 0,
                'percentage' => 0,
            ];
        }

        $watched = $user->watchlistEntries()
            ->select(['list_items.title_id'])
            ->whereIn('list_items.title_id', $episodeTitleIds)
            ->where('list_items.watch_state', WatchState::Completed)
            ->count();

        return [
            'total' => $total,
            'watched' => $watched,
            'remaining' => max(0, $total - $watched),
            'percentage' => (int) round(($watched / $total) * 100),
        ];
    }
}
