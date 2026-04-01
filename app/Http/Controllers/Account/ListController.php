<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\UserList;
use Illuminate\Contracts\View\View;

class ListController extends Controller
{
    public function __invoke(): View
    {
        $lists = UserList::query()
            ->select(['id', 'user_id', 'name', 'slug', 'description', 'visibility', 'is_watchlist', 'created_at'])
            ->whereBelongsTo(request()->user())
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
            ->latest('created_at')
            ->simplePaginate(12)
            ->withQueryString();

        return view('account.lists.index', [
            'lists' => $lists,
        ]);
    }
}
