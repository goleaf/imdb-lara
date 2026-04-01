<?php

namespace App\Actions\Titles;

use App\Models\Review;
use App\Models\Title;
use App\Models\User;

class GetUserReviewForTitleAction
{
    public function handle(User $user, Title $title): ?Review
    {
        return $user->reviews()
            ->select([
                'id',
                'user_id',
                'title_id',
                'headline',
                'body',
                'contains_spoilers',
                'status',
                'published_at',
                'moderated_at',
                'edited_at',
            ])
            ->where('title_id', $title->id)
            ->first();
    }
}
