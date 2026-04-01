<?php

namespace App\Actions\Lists;

use App\Models\UserList;

class DeleteUserListAction
{
    public function handle(UserList $list): void
    {
        $list->delete();
    }
}
