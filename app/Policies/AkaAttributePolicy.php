<?php

namespace App\Policies;

use App\Models\AkaAttribute;
use App\Models\User;

class AkaAttributePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageCatalog();
    }

    public function view(User $user, AkaAttribute $akaAttribute): bool
    {
        return $user->canManageCatalog();
    }

    public function create(User $user): bool
    {
        return $user->canManageCatalog();
    }

    public function update(User $user, AkaAttribute $akaAttribute): bool
    {
        return $user->canManageCatalog();
    }

    public function delete(User $user, AkaAttribute $akaAttribute): bool
    {
        return $user->canManageCatalog();
    }

    public function restore(User $user, AkaAttribute $akaAttribute): bool
    {
        return $user->canManageCatalog();
    }

    public function forceDelete(User $user, AkaAttribute $akaAttribute): bool
    {
        return $user->canManageCatalog();
    }
}
