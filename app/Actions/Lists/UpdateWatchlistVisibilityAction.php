<?php

namespace App\Actions\Lists;

use App\Enums\ListVisibility;
use App\Models\UserList;

class UpdateWatchlistVisibilityAction
{
    public function handle(UserList $watchlist, ListVisibility $visibility): UserList
    {
        $watchlist->forceFill([
            'visibility' => $visibility,
        ])->save();

        return $watchlist->refresh();
    }
}
