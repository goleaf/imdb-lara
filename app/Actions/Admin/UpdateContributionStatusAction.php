<?php

namespace App\Actions\Admin;

use App\Enums\ContributionStatus;
use App\Models\Contribution;
use App\Models\ModerationAction;
use App\Models\User;

class UpdateContributionStatusAction
{
    /**
     * @param  array{status: string, notes?: string|null}  $attributes
     */
    public function handle(User $reviewer, Contribution $contribution, array $attributes): Contribution
    {
        $previousStatus = $contribution->status;
        $nextStatus = ContributionStatus::from($attributes['status']);
        $reviewNotes = filled($attributes['notes'] ?? null) ? trim((string) $attributes['notes']) : null;

        $contribution->status = $nextStatus;
        $contribution->notes = $reviewNotes;
        $contribution->reviewed_by = $reviewer->id;
        $contribution->reviewed_at = now();
        $contribution->save();

        ModerationAction::query()->create([
            'moderator_id' => $reviewer->id,
            'report_id' => null,
            'actionable_type' => Contribution::class,
            'actionable_id' => $contribution->id,
            'action' => match ($nextStatus) {
                ContributionStatus::Approved => 'approve-contribution',
                ContributionStatus::Rejected => 'reject-contribution',
                ContributionStatus::Submitted => 'reopen-contribution',
            },
            'notes' => $reviewNotes,
            'metadata' => [
                'from_status' => $previousStatus->value,
                'to_status' => $nextStatus->value,
                'contributable_type' => $contribution->contributable_type,
                'contributable_id' => $contribution->contributable_id,
                'field' => $contribution->proposed_field,
            ],
        ]);

        return $contribution->refresh();
    }
}
