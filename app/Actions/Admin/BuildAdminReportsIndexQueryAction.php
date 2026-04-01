<?php

namespace App\Actions\Admin;

use App\Models\Report;
use Illuminate\Database\Eloquent\Builder;

class BuildAdminReportsIndexQueryAction
{
    public function handle(): Builder
    {
        return Report::query()
            ->select(['id', 'user_id', 'reportable_type', 'reportable_id', 'reason', 'status', 'reviewed_at'])
            ->with('reporter:id,name,username')
            ->latest();
    }
}
