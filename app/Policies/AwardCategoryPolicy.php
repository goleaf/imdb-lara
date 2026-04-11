<?php

namespace App\Policies;

use App\Models\AwardCategory;
use App\Models\User;

class AwardCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageCatalog();
    }

    public function view(User $user, AwardCategory $awardCategory): bool
    {
        return $user->canManageCatalog();
    }

    public function create(User $user): bool
    {
        return $user->canManageCatalog();
    }

    public function update(User $user, AwardCategory $awardCategory): bool
    {
        return $user->canManageCatalog();
    }

    public function delete(User $user, AwardCategory $awardCategory): bool
    {
        return $user->canManageCatalog();
    }

    public function restore(User $user, AwardCategory $awardCategory): bool
    {
        return $user->canManageCatalog();
    }

    public function forceDelete(User $user, AwardCategory $awardCategory): bool
    {
        return $user->canManageCatalog();
    }
}
