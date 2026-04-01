<?php

namespace App\Actions\Admin;

use App\Enums\ReportStatus;
use App\Enums\ReviewStatus;
use App\Models\Report;
use App\Models\Review;
use App\Models\Title;

class GetDashboardStatsAction
{
    /**
     * @return array{titles: int, pending_reviews: int, open_reports: int}
     */
    public function handle(): array
    {
        return [
            'titles' => Title::query()->count(),
            'pending_reviews' => Review::query()->where('status', ReviewStatus::Pending)->count(),
            'open_reports' => Report::query()->where('status', ReportStatus::Open)->count(),
        ];
    }
}
