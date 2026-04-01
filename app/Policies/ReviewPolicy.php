<?php

namespace App\Policies;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canModerateContent();
    }

    public function view(User $user, Review $review): bool
    {
        return $review->status === ReviewStatus::Published
            || $user->canModerateContent()
            || $review->author->is($user);
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(User $user, Review $review): bool
    {
        return $user->canModerateContent() || $review->author->is($user);
    }

    public function delete(User $user, Review $review): bool
    {
        return $user->canModerateContent() || $review->author->is($user);
    }

    public function voteHelpful(User $user, Review $review): bool
    {
        return $user->isActive()
            && $review->status === ReviewStatus::Published
            && ! $review->author->is($user);
    }

    public function report(User $user, Review $review): bool
    {
        return $user->isActive()
            && $review->status === ReviewStatus::Published
            && ! $review->author->is($user);
    }

    public function moderate(User $user, Review $review): bool
    {
        return $user->canModerateContent();
    }

    public function restore(User $user, Review $review): bool
    {
        return $user->canModerateContent();
    }

    public function forceDelete(User $user, Review $review): bool
    {
        return $user->canModerateContent();
    }
}
