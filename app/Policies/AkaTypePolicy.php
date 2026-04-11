<?php

namespace App\Policies;

use App\Models\AkaType;
use App\Models\User;

class AkaTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageCatalog();
    }

    public function view(User $user, AkaType $akaType): bool
    {
        return $user->canManageCatalog();
    }

    public function create(User $user): bool
    {
        return $user->canManageCatalog();
    }

    public function update(User $user, AkaType $akaType): bool
    {
        return $user->canManageCatalog();
    }

    public function delete(User $user, AkaType $akaType): bool
    {
        return $user->canManageCatalog();
    }

    public function restore(User $user, AkaType $akaType): bool
    {
        return $user->canManageCatalog();
    }

    public function forceDelete(User $user, AkaType $akaType): bool
    {
        return $user->canManageCatalog();
    }
}
