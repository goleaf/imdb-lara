<?php

namespace App\Actions\Lists;

use App\Models\UserList;

class LoadPublicUserListAction
{
    public function handle(UserList $list): UserList
    {
        $list->load([
            'user:id,name,username',
        ])->loadCount('items');

        return $list;
    }
}
