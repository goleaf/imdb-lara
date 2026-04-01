<?php

namespace App\Actions\Contributions;

use App\Enums\ContributionAction;
use App\Enums\ContributionStatus;
use App\Models\Contribution;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SubmitContributionAction
{
    /**
     * @param  array{field: string, field_label: string, value: string, notes?: string|null}  $attributes
     */
    public function handle(User $user, Model $contributable, array $attributes): Contribution
    {
        return Contribution::query()->create([
            'user_id' => $user->id,
            'contributable_type' => $contributable::class,
            'contributable_id' => $contributable->getKey(),
            'action' => ContributionAction::Update,
            'status' => ContributionStatus::Submitted,
            'payload' => [
                'field' => $attributes['field'],
                'field_label' => $attributes['field_label'],
                'value' => trim($attributes['value']),
                'submission_notes' => filled($attributes['notes'] ?? null) ? trim((string) $attributes['notes']) : null,
            ],
            'notes' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);
    }
}
