<?php

namespace App\Actions\Titles;

use App\Models\Rating;
use App\Models\Title;
use App\Models\User;

class DeleteRatingAction
{
    public function handle(User $user, Title $title): bool
    {
        $rating = Rating::query()
            ->select(['id', 'user_id', 'title_id', 'score'])
            ->whereBelongsTo($user)
            ->whereBelongsTo($title)
            ->first();

        if (! $rating) {
            return false;
        }

        $rating->delete();

        return true;
    }
}
