<?php

namespace App\Actions\Titles;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\Title;
use App\Models\User;

class StoreReviewAction
{
    /**
     * @param  array{headline?: string|null, body: string, contains_spoilers?: bool}  $attributes
     */
    public function handle(
        User $user,
        Title $title,
        array $attributes,
        ReviewStatus $requestedStatus = ReviewStatus::Pending,
    ): Review {
        $review = Review::query()
            ->withTrashed()
            ->firstOrNew([
                'user_id' => $user->id,
                'title_id' => $title->id,
            ]);

        $status = $this->resolveStatus($user, $requestedStatus);
        $wasExisting = $review->exists;
        $publishedAt = $review->published_at;

        $review->headline = filled($attributes['headline'] ?? null) ? (string) $attributes['headline'] : null;
        $review->body = $attributes['body'];
        $review->contains_spoilers = (bool) ($attributes['contains_spoilers'] ?? false);
        $review->status = $status;
        $review->moderated_by = $status === ReviewStatus::Published && $user->canModerateContent() ? $user->id : null;
        $review->moderated_at = $status === ReviewStatus::Published && $user->canModerateContent() ? now() : null;
        $review->published_at = $status === ReviewStatus::Published ? ($publishedAt ?? now()) : null;
        $review->edited_at = $wasExisting ? now() : null;
        $review->deleted_at = null;
        $review->save();

        return $review->refresh();
    }

    private function resolveStatus(User $user, ReviewStatus $requestedStatus): ReviewStatus
    {
        if ($requestedStatus === ReviewStatus::Draft) {
            return ReviewStatus::Draft;
        }

        return $user->canModerateContent()
            ? ReviewStatus::Published
            : ReviewStatus::Pending;
    }
}
