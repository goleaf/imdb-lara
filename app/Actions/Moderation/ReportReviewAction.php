<?php

namespace App\Actions\Moderation;

use App\Enums\ReviewStatus;
use App\Models\Report;
use App\Models\Review;
use App\Models\User;

class ReportReviewAction
{
    public function __construct(private UpsertReportAction $upsertReport) {}

    /**
     * @param  array{reason: string, details?: string|null}  $attributes
     */
    public function handle(User $user, Review $review, array $attributes): Report
    {
        abort_unless($review->status === ReviewStatus::Published, 404);

        return $this->upsertReport->handle($user, $review, $attributes);
    }
}
