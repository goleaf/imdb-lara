<?php

namespace App\Http\Controllers\Account;

use App\Actions\Lists\GetAccountWatchlistAction;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class WatchlistController extends Controller
{
    public function __invoke(GetAccountWatchlistAction $getAccountWatchlist): View
    {
        return view('account.watchlist', [
            'watchlist' => $getAccountWatchlist->handle(request()->user()),
        ]);
    }
}
