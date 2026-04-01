<?php

namespace App\Actions\Users;

use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BuildPublicUserRatingsQueryAction
{
    public function handle(User $profileUser): Builder
    {
        return Rating::query()
            ->select([
                'id',
                'user_id',
                'title_id',
                'score',
                'created_at',
            ])
            ->whereBelongsTo($profileUser)
            ->with([
                'title:id,name,slug,title_type,release_year,plot_outline',
                'title.mediaAssets:id,mediable_type,mediable_id,kind,url,alt_text,position,is_primary',
                'title.statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count',
            ])
            ->latest('created_at');
    }
}
