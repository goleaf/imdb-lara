<?php

namespace App\Actions\Lists;

use App\Enums\ListVisibility;
use App\Models\User;
use App\Models\UserList;

class EnsureWatchlistAction
{
    public function handle(User $user): UserList
    {
        return UserList::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'slug' => 'watchlist',
            ],
            [
                'name' => 'Watchlist',
                'description' => 'Titles queued by the member for future viewing.',
                'visibility' => ListVisibility::Private,
                'is_watchlist' => true,
            ],
        );
    }
}
