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
        if (in_array($userList->visibility, [ListVisibility::Public, ListVisibility::Unlisted], true)) {
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

    public function report(User $user, UserList $userList): bool
    {
        return $user->isActive()
            && $userList->isShareable()
            && $userList->user_id !== $user->id;
    }

    public function update(User $user, UserList $userList): bool
    {
        return $userList->user_id === $user->id && ! $userList->is_watchlist;
    }

    public function delete(User $user, UserList $userList): bool
    {
        return $userList->user_id === $user->id && ! $userList->is_watchlist;
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
