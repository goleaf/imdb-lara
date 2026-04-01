<?php

namespace App\Policies;

use App\Models\Episode;
use App\Models\User;

class EpisodePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageCatalog();
    }

    public function view(User $user, Episode $episode): bool
    {
        return $user->canManageCatalog();
    }

    public function create(User $user): bool
    {
        return $user->canManageCatalog();
    }

    public function update(User $user, Episode $episode): bool
    {
        return $user->canManageCatalog();
    }

    public function delete(User $user, Episode $episode): bool
    {
        return $user->canManageCatalog();
    }

    public function restore(User $user, Episode $episode): bool
    {
        return $user->canManageCatalog();
    }

    public function forceDelete(User $user, Episode $episode): bool
    {
        return $user->canManageCatalog();
    }
}
