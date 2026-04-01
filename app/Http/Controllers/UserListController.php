<?php

namespace App\Http\Controllers;

use App\Actions\Lists\LoadPublicUserListAction;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Contracts\View\View;

class UserListController extends Controller
{
    public function show(User $user, UserList $list, LoadPublicUserListAction $loadPublicUserList): View
    {
        return view('lists.show', [
            'list' => $loadPublicUserList->handle($user, $list),
            'owner' => $user,
        ]);
    }
}
