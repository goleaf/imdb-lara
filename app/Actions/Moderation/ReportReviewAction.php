<?php

namespace App\Actions\Moderation;

use App\Models\Report;
use App\Models\Review;
use App\Models\User;
use App\ReportReason;
use App\ReportStatus;
use App\ReviewStatus;

class ReportReviewAction
{
    /**
     * @param  array{reason: string, details?: string|null}  $attributes
     */
    public function handle(User $user, Review $review, array $attributes): Report
    {
        abort_unless($review->status === ReviewStatus::Published, 404);

        return Report::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'reportable_type' => Review::class,
                'reportable_id' => $review->id,
            ],
            [
                'reason' => ReportReason::from($attributes['reason']),
                'details' => $attributes['details'] ?? null,
                'status' => ReportStatus::Open,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ],
        );
    }
}
