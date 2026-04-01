<?php

namespace App\Actions\Admin;

use App\Enums\ContributionStatus;
use App\Models\Contribution;
use App\Models\User;

class UpdateContributionStatusAction
{
    /**
     * @param  array{status: string, notes?: string|null}  $attributes
     */
    public function handle(User $reviewer, Contribution $contribution, array $attributes): Contribution
    {
        $contribution->status = ContributionStatus::from($attributes['status']);
        $contribution->notes = filled($attributes['notes'] ?? null) ? trim((string) $attributes['notes']) : null;
        $contribution->reviewed_by = $reviewer->id;
        $contribution->reviewed_at = now();
        $contribution->save();

        return $contribution->refresh();
    }
}
