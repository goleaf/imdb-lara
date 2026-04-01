<?php

namespace App\Actions\Catalog;

use App\Models\Review;
use App\Models\Title;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BuildTitleReviewsQueryAction
{
    public function handle(Title $title, string $sort = 'newest', ?User $viewer = null): Builder
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
            ->forTitle($title)
            ->published()
            ->with([
                'author:id,name,username',
            ])
            ->withHelpfulMetrics($viewer)
            ->orderForPublic($sort);
    }
}
