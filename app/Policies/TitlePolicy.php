<?php

namespace App\Policies;

use App\Models\Title;
use App\Models\User;

class TitlePolicy
{
    public function track(User $user, Title $title): bool
    {
        return $user->isActive();
    }

    public function viewAny(User $user): bool
    {
        return $user->canManageCatalog();
    }

    public function view(User $user, Title $title): bool
    {
        return $user->canManageCatalog();
    }

    public function create(User $user): bool
    {
        return $user->canManageCatalog();
    }

    public function update(User $user, Title $title): bool
    {
        return $user->canManageCatalog();
    }

    public function delete(User $user, Title $title): bool
    {
        return $user->canManageCatalog();
    }

    public function restore(User $user, Title $title): bool
    {
        return $user->canManageCatalog();
    }

    public function forceDelete(User $user, Title $title): bool
    {
        return $user->canManageCatalog();
    }
}
