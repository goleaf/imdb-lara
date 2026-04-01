<?php

namespace App\Policies;

use App\Models\Contribution;
use App\Models\User;

class ContributionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canReviewContributions();
    }

    public function view(User $user, Contribution $contribution): bool
    {
        return $user->canReviewContributions() || $contribution->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->canSubmitContributions();
    }

    public function update(User $user, Contribution $contribution): bool
    {
        return $user->canReviewContributions()
            || ($contribution->user_id === $user->id && $contribution->reviewed_by === null);
    }

    public function delete(User $user, Contribution $contribution): bool
    {
        return $user->canReviewContributions()
            || ($contribution->user_id === $user->id && $contribution->reviewed_by === null);
    }

    public function restore(User $user, Contribution $contribution): bool
    {
        return $user->canReviewContributions();
    }

    public function forceDelete(User $user, Contribution $contribution): bool
    {
        return $user->canReviewContributions();
    }
}
