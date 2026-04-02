<?php

namespace App\Actions\Admin;

use App\Enums\ContributionStatus;
use App\Models\Contribution;
use Illuminate\Database\Eloquent\Builder;

class BuildAdminContributionsIndexQueryAction
{
    public function handle(): Builder
    {
        return Contribution::query()
            ->select([
                'id',
                'user_id',
                'contributable_type',
                'contributable_id',
                'action',
                'status',
                'payload',
                'notes',
                'reviewed_by',
                'reviewed_at',
                'created_at',
            ])
            ->with([
                'user:id,name,username',
                'reviewer:id,name,username',
                'contributable',
            ])
            ->orderByRaw(
                'case status
                    when ? then 0
                    when ? then 1
                    when ? then 2
                    else 3
                end',
                [
                    ContributionStatus::Submitted->value,
                    ContributionStatus::Approved->value,
                    ContributionStatus::Rejected->value,
                ],
            )
            ->latest('created_at')
            ->latest('id');
    }
}
