<?php

namespace App\Policies;

use App\Models\Credit;
use App\Models\User;

class CreditPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageCatalog();
    }

    public function view(User $user, Credit $credit): bool
    {
        return $user->canManageCatalog();
    }

    public function create(User $user): bool
    {
        return $user->canManageCatalog();
    }

    public function update(User $user, Credit $credit): bool
    {
        return $user->canManageCatalog();
    }

    public function delete(User $user, Credit $credit): bool
    {
        return $user->canManageCatalog();
    }

    public function restore(User $user, Credit $credit): bool
    {
        return $user->canManageCatalog();
    }

    public function forceDelete(User $user, Credit $credit): bool
    {
        return $user->canManageCatalog();
    }
}
