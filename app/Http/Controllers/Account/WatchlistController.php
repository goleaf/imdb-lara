<?php

namespace App\Http\Controllers\Account;

use App\Actions\Lists\EnsureWatchlistAction;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class WatchlistController extends Controller
{
    public function __invoke(EnsureWatchlistAction $ensureWatchlist): View
    {
        $watchlist = $ensureWatchlist->handle(request()->user());

        $watchlist->load([
            'items.title.statistic',
            'items.title.mediaAssets',
        ]);

        return view('account.watchlist', [
            'watchlist' => $watchlist,
        ]);
    }
}
