<?php

namespace App\Actions\Admin;

use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Database\Eloquent\Builder;

class BuildAdminReportsIndexQueryAction
{
    public function handle(): Builder
    {
        return Report::query()
            ->select([
                'id',
                'user_id',
                'reportable_type',
                'reportable_id',
                'reason',
                'details',
                'status',
                'reviewed_by',
                'reviewed_at',
                'resolution_notes',
                'created_at',
            ])
            ->with(Report::adminQueueRelations())
            ->orderByRaw(
                'case status
                    when ? then 0
                    when ? then 1
                    when ? then 2
                    when ? then 3
                    else 4
                end',
                [
                    ReportStatus::Open->value,
                    ReportStatus::Investigating->value,
                    ReportStatus::Resolved->value,
                    ReportStatus::Dismissed->value,
                ],
            )
            ->latest('created_at')
            ->latest('id');
    }
}
