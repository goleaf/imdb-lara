<?php

namespace App\Actions\Admin;

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
                'status_priority',
                'reviewed_by',
                'reviewed_at',
                'resolution_notes',
                'created_at',
            ])
            ->with(Report::adminQueueRelations())
            ->orderBy('status_priority')
            ->latest('created_at')
            ->latest('id');
    }
}
