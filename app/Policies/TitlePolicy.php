<?php

namespace App\Policies;

use App\Models\Title;
use App\Models\User;

class TitlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Title $title): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Title $title): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Title $title): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Title $title): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Title $title): bool
    {
        return $user->isAdmin();
    }
}
