<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\UpdateContributionStatusAction;
use App\Actions\Admin\UpdateReportStatusAction;
use App\Actions\Moderation\ModerateReviewAction;
use App\Enums\ReviewStatus;
use App\Http\Requests\Admin\UpdateContributionRequest;
use App\Http\Requests\Admin\UpdateReportRequest;
use App\Http\Requests\Admin\UpdateReviewModerationRequest;
use App\Models\Contribution;
use App\Models\Report;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class ModerationAdminController
{
    public function updateReview(
        UpdateReviewModerationRequest $request,
        Review $review,
        ModerateReviewAction $moderateReview,
    ): RedirectResponse {
        /** @var User $moderator */
        $moderator = $request->user();
        $validated = $request->validated();

        $moderateReview->handle(
            $moderator,
            $review,
            ReviewStatus::from($validated['status']),
            $validated['moderation_notes'] ?? null,
        );

        return redirect()
            ->route('admin.reviews.index')
            ->with('status', 'Review moderation saved.');
    }

    public function updateReport(
        UpdateReportRequest $request,
        Report $report,
        UpdateReportStatusAction $updateReportStatus,
    ): RedirectResponse {
        /** @var User $moderator */
        $moderator = $request->user();

        $updateReportStatus->handle($moderator, $report, $request->validated());

        return redirect()
            ->route('admin.reports.index')
            ->with('status', 'Report moderation saved.');
    }

    public function updateContribution(
        UpdateContributionRequest $request,
        Contribution $contribution,
        UpdateContributionStatusAction $updateContributionStatus,
    ): RedirectResponse {
        /** @var User $reviewer */
        $reviewer = $request->user();

        $updateContributionStatus->handle($reviewer, $contribution, $request->validated());

        return redirect()
            ->route('admin.contributions.index')
            ->with('status', 'Contribution review saved.');
    }
}
