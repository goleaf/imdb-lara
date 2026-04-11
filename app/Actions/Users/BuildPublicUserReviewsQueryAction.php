<?php

namespace App\Actions\Users;

use App\Models\Review;
use App\Models\Title;
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
                'title' => fn ($titleQuery) => $titleQuery
                    ->select(Title::catalogCardColumns())
                    ->publishedCatalog()
                    ->withCatalogCardRelations(),
                'author:id,name,username',
            ])
            ->withHelpfulMetrics($viewer)
            ->orderForPublic('newest');
    }
}
