<?php

namespace App\Actions\Users;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BuildPublicUserReviewsQueryAction
{
    public function handle(User $profileUser, ?User $viewer = null): Builder
    {
        return Review::query()
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
                'created_at',
            ])
            ->authoredBy($profileUser)
            ->published()
            ->with([
                'title:id,name,slug,title_type,release_year',
                'author:id,name,username',
            ])
            ->withHelpfulMetrics($viewer)
            ->orderForPublic('newest');
    }
}
