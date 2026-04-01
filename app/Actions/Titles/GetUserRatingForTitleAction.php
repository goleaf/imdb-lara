<?php

namespace App\Actions\Titles;

use App\Models\Title;
use App\Models\User;

class GetUserRatingForTitleAction
{
    public function handle(User $user, Title $title): ?int
    {
        return $user->ratings()
            ->where('title_id', $title->id)
            ->value('score');
    }
}
