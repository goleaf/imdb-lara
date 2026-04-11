<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\UpdateContributionStatusAction;
use App\Actions\Admin\UpdateReportStatusAction;
use App\Actions\Moderation\ModerateReviewAction;
use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateContributionRequest;
use App\Http\Requests\Admin\UpdateReportRequest;
use App\Http\Requests\Admin\UpdateReviewModerationRequest;
use App\Models\Contribution;
use App\Models\Report;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class ModerationController extends Controller
{
    public function updateReview(
        UpdateReviewModerationRequest $request,
        Review $review,
        ModerateReviewAction $moderateReview,
    ): RedirectResponse {
        $validated = $request->validated();
        $moderator = $request->user();
        abort_unless($moderator instanceof User, 403);

        $moderateReview->handle(
            $moderator,
            $review,
            ReviewStatus::from($validated['status']),
            $validated['moderation_notes'] ?? null,
        );

        return back()->with('status', 'Review moderation saved.');
    }

    public function updateReport(
        UpdateReportRequest $request,
        Report $report,
        UpdateReportStatusAction $updateReportStatus,
    ): RedirectResponse {
        $moderator = $request->user();
        abort_unless($moderator instanceof User, 403);

        $updateReportStatus->handle($moderator, $report, $request->validated());

        return back()->with('status', 'Report moderation saved.');
    }

    public function updateContribution(
        UpdateContributionRequest $request,
        Contribution $contribution,
        UpdateContributionStatusAction $updateContributionStatus,
    ): RedirectResponse {
        $reviewer = $request->user();
        abort_unless($reviewer instanceof User, 403);

        $updateContributionStatus->handle($reviewer, $contribution, $request->validated());

        return back()->with('status', 'Contribution review saved.');
    }
}
