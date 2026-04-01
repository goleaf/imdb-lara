<?php

namespace App\Http\Requests\Lists;

use App\Http\Requests\NotFoundFormRequest;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Support\Facades\Gate;

class ShowUserListRequest extends NotFoundFormRequest
{
    public function authorize(): bool
    {
        $owner = $this->route('user');
        $list = $this->route('list');

        return $owner instanceof User
            && $list instanceof UserList
            && $list->user_id === $owner->id
            && Gate::allows('view', $list);
    }

    public function owner(): User
    {
        /** @var User */
        return $this->route('user');
    }

    public function userList(): UserList
    {
        /** @var UserList */
        return $this->route('list');
    }
}
