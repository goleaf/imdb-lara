<?php

namespace App\Policies;

use App\Models\PersonProfession;
use App\Models\User;

class PersonProfessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageCatalog();
    }

    public function view(User $user, PersonProfession $personProfession): bool
    {
        return $user->canManageCatalog();
    }

    public function create(User $user): bool
    {
        return $user->canManageCatalog();
    }

    public function update(User $user, PersonProfession $personProfession): bool
    {
        return $user->canManageCatalog();
    }

    public function delete(User $user, PersonProfession $personProfession): bool
    {
        return $user->canManageCatalog();
    }

    public function restore(User $user, PersonProfession $personProfession): bool
    {
        return $user->canManageCatalog();
    }

    public function forceDelete(User $user, PersonProfession $personProfession): bool
    {
        return $user->canManageCatalog();
    }
}
