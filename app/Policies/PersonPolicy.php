<?php

namespace App\Policies;

use App\Models\Person;
use App\Models\User;

class PersonPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageCatalog();
    }

    public function view(User $user, Person $person): bool
    {
        return $user->canManageCatalog();
    }

    public function create(User $user): bool
    {
        return $user->canManageCatalog();
    }

    public function update(User $user, Person $person): bool
    {
        return $user->canManageCatalog();
    }

    public function delete(User $user, Person $person): bool
    {
        return $user->canManageCatalog();
    }

    public function restore(User $user, Person $person): bool
    {
        return $user->canManageCatalog();
    }

    public function forceDelete(User $user, Person $person): bool
    {
        return $user->canManageCatalog();
    }
}
