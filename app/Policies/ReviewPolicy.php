<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;
use App\ReviewStatus;

class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Review $review): bool
    {
        return $review->status === ReviewStatus::Published || $user->isAdmin() || $review->author->is($user);
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(User $user, Review $review): bool
    {
        return $user->isAdmin() || $review->author->is($user);
    }

    public function delete(User $user, Review $review): bool
    {
        return $user->isAdmin() || $review->author->is($user);
    }

    public function restore(User $user, Review $review): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Review $review): bool
    {
        return $user->isAdmin();
    }
}
