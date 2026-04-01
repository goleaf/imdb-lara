<?php

namespace App\Actions\Lists;

use App\Models\User;
use App\Models\UserList;
use Illuminate\Database\Eloquent\Builder;

class BuildAccountListsIndexQueryAction
{
    public function handle(User $user): Builder
    {
        return UserList::query()
            ->select(['id', 'user_id', 'name', 'slug', 'description', 'visibility', 'is_watchlist', 'created_at'])
            ->whereBelongsTo($user)
            ->where('is_watchlist', false)
            ->withCount('items')
            ->with([
                'items' => fn ($query) => $query
                    ->select(['id', 'user_list_id', 'title_id', 'position'])
                    ->with([
                        'title:id,name,slug,title_type,release_year',
                        'title.mediaAssets:id,mediable_type,mediable_id,kind,url,alt_text,position,is_primary',
                        'title.statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count',
                    ])
                    ->limit(3),
            ])
            ->latest('created_at');
    }
}
