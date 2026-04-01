<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\NotFoundFormRequest;
use App\Models\User;
use App\Models\UserList;

class ShowAccountListRequest extends NotFoundFormRequest
{
    protected ?UserList $userList = null;

    public function authorize(): bool
    {
        $user = $this->user();
        $slug = (string) $this->route('list');

        if (! $user instanceof User || $slug === '') {
            return false;
        }

        $this->userList = UserList::query()
            ->select([
                'id',
                'user_id',
                'name',
                'slug',
                'description',
                'visibility',
                'is_watchlist',
                'meta_title',
                'meta_description',
                'created_at',
                'updated_at',
            ])
            ->whereBelongsTo($user)
            ->custom()
            ->where('slug', $slug)
            ->first();

        return $this->userList instanceof UserList
            && $user->can('update', $this->userList);
    }

    public function userList(): UserList
    {
        if ($this->userList instanceof UserList) {
            return $this->userList;
        }

        /** @var User $user */
        $user = $this->user();

        return UserList::query()
            ->whereBelongsTo($user)
            ->custom()
            ->where('slug', (string) $this->route('list'))
            ->firstOrFail();
    }
}
