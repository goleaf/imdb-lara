<?php

namespace App\Actions\Lists;

use App\Models\User;
use App\Models\UserList;
use Illuminate\Database\Eloquent\Builder;

class BuildOwnedCustomListsQueryAction
{
    public function handle(User $user): Builder
    {
        return UserList::query()
            ->select(['id', 'user_id', 'name', 'slug', 'visibility', 'is_watchlist'])
            ->whereBelongsTo($user)
            ->where('is_watchlist', false)
            ->orderBy('name');
    }
}
