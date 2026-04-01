<?php

namespace App\Actions\Lists;

use App\Models\User;
use App\Models\UserList;

class GetAccountWatchlistAction
{
    public function __construct(
        public EnsureWatchlistAction $ensureWatchlist,
    ) {}

    public function handle(User $user): UserList
    {
        $watchlist = $this->ensureWatchlist->handle($user);

        $watchlist->load([
            'items.title.statistic',
            'items.title.mediaAssets',
        ]);

        return $watchlist;
    }
}
