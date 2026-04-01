<?php

namespace App\Http\Controllers\Account;

use App\Actions\Lists\BuildAccountListsIndexQueryAction;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class ListController extends Controller
{
    public function __invoke(BuildAccountListsIndexQueryAction $buildAccountListsIndexQuery): View
    {
        $lists = $buildAccountListsIndexQuery
            ->handle(request()->user())
            ->simplePaginate(12)
            ->withQueryString();

        return view('account.lists.index', [
            'lists' => $lists,
        ]);
    }
}
