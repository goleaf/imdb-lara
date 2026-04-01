<?php

namespace App\Actions\Lists;

use App\Models\Title;
use App\Models\User;
use App\Models\UserList;

class IsTitleInWatchlistAction
{
    public function handle(User $user, Title $title): bool
    {
        return UserList::query()
            ->select(['id'])
            ->whereBelongsTo($user)
            ->where('is_watchlist', true)
            ->whereHas('items', fn ($query) => $query->where('title_id', $title->id))
            ->exists();
    }
}
