<?php

namespace App\Policies;

use App\Models\Genre;
use App\Models\User;

class GenrePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageCatalog();
    }

    public function view(User $user, Genre $genre): bool
    {
        return $user->canManageCatalog();
    }

    public function create(User $user): bool
    {
        return $user->canManageCatalog();
    }

    public function update(User $user, Genre $genre): bool
    {
        return $user->canManageCatalog();
    }

    public function delete(User $user, Genre $genre): bool
    {
        return $user->canManageCatalog();
    }

    public function restore(User $user, Genre $genre): bool
    {
        return $user->canManageCatalog();
    }

    public function forceDelete(User $user, Genre $genre): bool
    {
        return $user->canManageCatalog();
    }
}
