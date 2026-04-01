<?php

namespace App\Actions\Moderation;

use App\Enums\ReportStatus;
use App\Enums\ReviewStatus;
use App\Models\ModerationAction;
use App\Models\Review;
use App\Models\User;

class ModerateReviewAction
{
    public function handle(
        User $moderator,
        Review $review,
        ReviewStatus $status,
        ?string $notes = null,
    ): Review {
        $previousStatus = $review->status;
        $now = now();
        $openReports = $review->reports()
            ->select(['id'])
            ->where('status', ReportStatus::Open)
            ->get();

        $review->status = $status;
        $review->moderated_by = $moderator->id;
        $review->moderated_at = $now;
        $review->published_at = $status === ReviewStatus::Published
            ? ($review->published_at ?? $now)
            : null;
        $review->save();

        if ($openReports->isNotEmpty()) {
            $review->reports()
                ->whereKey($openReports->modelKeys())
                ->update([
                    'status' => $status === ReviewStatus::Published
                        ? ReportStatus::Dismissed
                        : ReportStatus::Resolved,
                    'reviewed_by' => $moderator->id,
                    'reviewed_at' => $now,
                ]);
        }

        ModerationAction::query()->create([
            'moderator_id' => $moderator->id,
            'report_id' => $openReports->count() === 1 ? $openReports->first()->id : null,
            'actionable_type' => Review::class,
            'actionable_id' => $review->id,
            'action' => $status === ReviewStatus::Published ? 'approve' : 'reject',
            'notes' => $notes,
            'metadata' => [
                'from_status' => $previousStatus->value,
                'to_status' => $status->value,
                'resolved_report_ids' => $openReports->modelKeys(),
            ],
        ]);

        return $review->refresh();
    }
}
