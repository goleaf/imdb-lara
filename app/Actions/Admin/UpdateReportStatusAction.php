<?php

namespace App\Actions\Admin;

use App\Enums\ListVisibility;
use App\Enums\ReportStatus;
use App\Enums\ReviewStatus;
use App\Enums\UserStatus;
use App\Models\ModerationAction;
use App\Models\Report;
use App\Models\Review;
use App\Models\User;
use App\Models\UserList;

class UpdateReportStatusAction
{
    /**
     * @param  array{status: string, content_action?: string|null, resolution_notes?: string|null, suspend_owner?: bool|null}  $attributes
     */
    public function handle(User $moderator, Report $report, array $attributes): Report
    {
        $report->loadMissing(Report::adminQueueRelations());

        $status = ReportStatus::from($attributes['status']);
        $contentAction = $attributes['content_action'] ?? 'none';
        $resolutionNotes = filled($attributes['resolution_notes'] ?? null)
            ? trim((string) $attributes['resolution_notes'])
            : null;
        $shouldSuspendOwner = (bool) ($attributes['suspend_owner'] ?? false);
        $previousStatus = $report->status;

        if ($contentAction === 'hide_content') {
            $this->hideReportedContent($moderator, $report, $resolutionNotes);
        }

        if ($shouldSuspendOwner) {
            $this->suspendReportedContentOwner($moderator, $report, $resolutionNotes);
        }

        $report->status = $status;
        $report->resolution_notes = $resolutionNotes;
        $report->reviewed_by = $moderator->id;
        $report->reviewed_at = now();
        $report->save();

        ModerationAction::query()->create([
            'moderator_id' => $moderator->id,
            'report_id' => $report->id,
            'actionable_type' => $report->reportable_type,
            'actionable_id' => $report->reportable_id,
            'action' => match ($status) {
                ReportStatus::Dismissed => 'dismiss-report',
                ReportStatus::Investigating => 'investigate-report',
                ReportStatus::Resolved => 'resolve-report',
                ReportStatus::Open => 'reopen-report',
            },
            'notes' => $resolutionNotes,
            'metadata' => [
                'from_status' => $previousStatus->value,
                'to_status' => $status->value,
                'content_action' => $contentAction,
                'suspended_owner' => $shouldSuspendOwner,
            ],
        ]);

        return $report->refresh();
    }

    private function hideReportedContent(User $moderator, Report $report, ?string $notes): void
    {
        $reportable = $report->reportable;

        if ($reportable instanceof Review) {
            $previousStatus = $reportable->status;
            $reportable->status = ReviewStatus::Rejected;
            $reportable->moderated_by = $moderator->id;
            $reportable->moderated_at = now();
            $reportable->published_at = null;
            $reportable->save();

            ModerationAction::query()->create([
                'moderator_id' => $moderator->id,
                'report_id' => $report->id,
                'actionable_type' => Review::class,
                'actionable_id' => $reportable->id,
                'action' => 'hide-content',
                'notes' => $notes,
                'metadata' => [
                    'from_status' => $previousStatus->value,
                    'to_status' => $reportable->status->value,
                ],
            ]);

            return;
        }

        if ($reportable instanceof UserList) {
            $previousVisibility = $reportable->visibility;
            $reportable->visibility = ListVisibility::Private;
            $reportable->save();

            ModerationAction::query()->create([
                'moderator_id' => $moderator->id,
                'report_id' => $report->id,
                'actionable_type' => UserList::class,
                'actionable_id' => $reportable->id,
                'action' => 'hide-content',
                'notes' => $notes,
                'metadata' => [
                    'from_visibility' => $previousVisibility->value,
                    'to_visibility' => $reportable->visibility->value,
                ],
            ]);
        }
    }

    private function suspendReportedContentOwner(User $moderator, Report $report, ?string $notes): void
    {
        $owner = match (true) {
            $report->reportable instanceof Review => $report->reportable->author,
            $report->reportable instanceof UserList => $report->reportable->user,
            default => null,
        };

        if (! $owner instanceof User || $owner->canAccessAdminPanel() || $owner->status === UserStatus::Suspended) {
            return;
        }

        $previousStatus = $owner->status;
        $owner->status = UserStatus::Suspended;
        $owner->save();

        ModerationAction::query()->create([
            'moderator_id' => $moderator->id,
            'report_id' => $report->id,
            'actionable_type' => User::class,
            'actionable_id' => $owner->id,
            'action' => 'suspend-user',
            'notes' => $notes,
            'metadata' => [
                'from_status' => $previousStatus->value,
                'to_status' => $owner->status->value,
            ],
        ]);
    }
}
