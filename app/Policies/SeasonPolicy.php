<?php

namespace App\Policies;

use App\Models\Season;
use App\Models\User;

class SeasonPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageCatalog();
    }

    public function view(User $user, Season $season): bool
    {
        return $user->canManageCatalog();
    }

    public function create(User $user): bool
    {
        return $user->canManageCatalog();
    }

    public function update(User $user, Season $season): bool
    {
        return $user->canManageCatalog();
    }

    public function delete(User $user, Season $season): bool
    {
        return $user->canManageCatalog();
    }

    public function restore(User $user, Season $season): bool
    {
        return $user->canManageCatalog();
    }

    public function forceDelete(User $user, Season $season): bool
    {
        return $user->canManageCatalog();
    }
}
