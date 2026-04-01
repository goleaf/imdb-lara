<?php

namespace App\Policies;

use App\Enums\ListVisibility;
use App\Models\User;
use App\Models\UserList;

class UserListPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(?User $user, UserList $userList): bool
    {
        if ($userList->visibility === ListVisibility::Public && ! $userList->is_watchlist) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $user->canModerateContent() || $userList->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(User $user, UserList $userList): bool
    {
        return ($user->canModerateContent() || $userList->user_id === $user->id) && ! $userList->is_watchlist;
    }

    public function delete(User $user, UserList $userList): bool
    {
        return ($user->canModerateContent() || $userList->user_id === $user->id) && ! $userList->is_watchlist;
    }

    public function restore(User $user, UserList $userList): bool
    {
        return $user->canModerateContent();
    }

    public function forceDelete(User $user, UserList $userList): bool
    {
        return $user->canModerateContent();
    }
}
