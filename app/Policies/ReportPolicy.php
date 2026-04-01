<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canModerateContent();
    }

    public function view(User $user, Report $report): bool
    {
        return $user->canModerateContent() || $report->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(User $user, Report $report): bool
    {
        return $user->canModerateContent();
    }

    public function delete(User $user, Report $report): bool
    {
        return $user->canModerateContent();
    }

    public function restore(User $user, Report $report): bool
    {
        return $user->canModerateContent();
    }

    public function forceDelete(User $user, Report $report): bool
    {
        return $user->canModerateContent();
    }
}
