<?php

namespace App\Policies;

use App\Models\MediaAsset;
use App\Models\User;

class MediaAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageMedia();
    }

    public function view(User $user, MediaAsset $mediaAsset): bool
    {
        return $user->canManageMedia();
    }

    public function create(User $user): bool
    {
        return $user->canManageMedia();
    }

    public function update(User $user, MediaAsset $mediaAsset): bool
    {
        return $user->canManageMedia();
    }

    public function delete(User $user, MediaAsset $mediaAsset): bool
    {
        return $user->canManageMedia();
    }

    public function restore(User $user, MediaAsset $mediaAsset): bool
    {
        return $user->canManageMedia();
    }

    public function forceDelete(User $user, MediaAsset $mediaAsset): bool
    {
        return $user->canManageMedia();
    }
}
