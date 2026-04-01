<?php

namespace App\Actions\Admin;

use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;

class BuildAdminReviewsIndexQueryAction
{
    public function handle(): Builder
    {
        return Review::query()
            ->select(['id', 'user_id', 'title_id', 'headline', 'status', 'published_at'])
            ->with([
                'author:id,name,username',
                'title:id,name,slug',
            ])
            ->latest();
    }
}
