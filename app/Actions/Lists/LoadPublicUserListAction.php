<?php

namespace App\Actions\Lists;

use App\Models\User;
use App\Models\UserList;
use Illuminate\Support\Facades\Gate;

class LoadPublicUserListAction
{
    public function handle(User $owner, UserList $list): UserList
    {
        abort_unless($list->user_id === $owner->id, 404);
        abort_unless(Gate::allows('view', $list), 404);

        $list->load([
            'user:id,name,username',
            'items' => fn ($query) => $query
                ->select(['id', 'user_list_id', 'title_id', 'notes', 'position'])
                ->with([
                    'title:id,name,slug,title_type,release_year,plot_outline',
                    'title.mediaAssets:id,mediable_type,mediable_id,kind,url,alt_text,position,is_primary',
                    'title.statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count',
                    'title.genres:id,name,slug',
                ]),
        ])->loadCount('items');

        return $list;
    }
}
