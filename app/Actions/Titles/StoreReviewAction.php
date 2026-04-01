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
    public function handle(User $user, Title $title, array $attributes): Review
    {
        $status = $user->canModerateContent() ? ReviewStatus::Published : ReviewStatus::Pending;

        return Review::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'title_id' => $title->id,
            ],
            [
                'headline' => $attributes['headline'] ?: null,
                'body' => $attributes['body'],
                'contains_spoilers' => (bool) ($attributes['contains_spoilers'] ?? false),
                'status' => $status,
                'moderated_by' => $user->canModerateContent() ? $user->id : null,
                'moderated_at' => $user->canModerateContent() ? now() : null,
                'published_at' => $status === ReviewStatus::Published ? now() : null,
            ],
        );
    }
}
