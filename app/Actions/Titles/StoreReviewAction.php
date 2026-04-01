<?php

namespace App\Actions\Titles;

use App\Models\Review;
use App\Models\Title;
use App\Models\User;
use App\ReviewStatus;

class StoreReviewAction
{
    public function __construct(
        public RefreshTitleStatisticsAction $refreshTitleStatistics,
    ) {}

    /**
     * @param  array{headline?: string|null, body: string, contains_spoilers?: bool}  $attributes
     */
    public function handle(User $user, Title $title, array $attributes): Review
    {
        $status = $user->isAdmin() ? ReviewStatus::Published : ReviewStatus::Pending;

        $review = Review::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'title_id' => $title->id,
            ],
            [
                'headline' => $attributes['headline'] ?: null,
                'body' => $attributes['body'],
                'contains_spoilers' => (bool) ($attributes['contains_spoilers'] ?? false),
                'status' => $status,
                'moderated_by' => $user->isAdmin() ? $user->id : null,
                'moderated_at' => $user->isAdmin() ? now() : null,
                'published_at' => $status === ReviewStatus::Published ? now() : null,
            ],
        );

        $this->refreshTitleStatistics->handle($title);

        return $review;
    }
}
