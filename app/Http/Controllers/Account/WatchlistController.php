<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ShowWatchlistRequest;
use Illuminate\Contracts\View\View;

class WatchlistController extends Controller
{
    public function __invoke(ShowWatchlistRequest $request): View
    {
        return view('account.watchlist');
    }
}
