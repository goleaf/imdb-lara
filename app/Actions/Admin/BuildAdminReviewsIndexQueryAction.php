<?php

namespace App\Actions\Admin;

use App\Enums\ReportStatus;
use App\Enums\ReviewStatus;
use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;

class BuildAdminReviewsIndexQueryAction
{
    /**
     * @param  array{status?: string|null, sort?: string|null, flaggedOnly?: bool}  $filters
     */
    public function handle(array $filters = []): Builder
    {
        $status = (string) ($filters['status'] ?? 'pending');
        $sort = (string) ($filters['sort'] ?? 'flagged');
        $flaggedOnly = (bool) ($filters['flaggedOnly'] ?? false);

        $query = Review::query()
            ->select([
                'id',
                'user_id',
                'title_id',
                'headline',
                'body',
                'contains_spoilers',
                'status',
                'published_at',
                'edited_at',
                'moderated_at',
                'created_at',
            ])
            ->with(Review::adminQueueRelations())
            ->withModerationMetrics();

        if ($status !== 'all') {
            $query->where('status', ReviewStatus::from($status));
        }

        if ($flaggedOnly) {
            $query->whereHas('reports', fn (Builder $reportQuery) => $reportQuery->where('status', ReportStatus::Open));
        }

        return match ($sort) {
            'helpful' => $query
                ->orderByDesc('helpful_votes_count')
                ->orderByDesc('published_at')
                ->orderByDesc('id'),
            'oldest' => $query->oldest('created_at')->oldest('id'),
            default => $query
                ->orderByDesc('open_reports_count')
                ->latest('created_at'),
        };
    }
}
