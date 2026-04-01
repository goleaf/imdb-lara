<?php

namespace App\Actions\Titles;

use App\Models\Review;
use App\Models\ReviewVote;
use App\Models\User;

class ToggleReviewHelpfulVoteAction
{
    /**
     * @return array{helpfulCount: int, hasHelpfulVote: bool}
     */
    public function handle(User $user, Review $review): array
    {
        $existingVote = ReviewVote::query()
            ->select(['id', 'review_id', 'user_id', 'is_helpful'])
            ->whereBelongsTo($review)
            ->whereBelongsTo($user)
            ->first();

        if ($existingVote?->is_helpful) {
            $existingVote->delete();

            return [
                'helpfulCount' => $review->helpfulVotes()->count(),
                'hasHelpfulVote' => false,
            ];
        }

        ReviewVote::query()->updateOrCreate(
            [
                'review_id' => $review->id,
                'user_id' => $user->id,
            ],
            [
                'is_helpful' => true,
            ],
        );

        return [
            'helpfulCount' => $review->helpfulVotes()->count(),
            'hasHelpfulVote' => true,
        ];
    }
}
