<?php

namespace App\Http\Controllers;

use App\Enums\ListVisibility;
use App\Enums\ReviewStatus;
use App\Http\Requests\Users\ShowUserProfileRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;

class UserProfileController extends Controller
{
    public function __invoke(ShowUserProfileRequest $request, User $user): View
    {
        $user = $request->profileUser();
        $publicWatchlist = $user->publicWatchlist()
            ->select(['id', 'user_id', 'name', 'slug', 'description', 'visibility', 'is_watchlist', 'created_at'])
            ->withCount('items')
            ->with([
                'items' => fn ($query) => $query
                    ->select(['id', 'user_list_id', 'title_id', 'position'])
                    ->latest('created_at')
                    ->limit(3)
                    ->with([
                        'title:id,name,slug,title_type,release_year,plot_outline',
                        'title.mediaAssets:id,mediable_type,mediable_id,kind,url,alt_text,position,is_primary',
                        'title.statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count',
                        'title.genres:id,name,slug',
                    ]),
            ])
            ->first();

        $publicLists = $user->customLists()
            ->select(['id', 'user_id', 'name', 'slug', 'description', 'visibility', 'is_watchlist', 'created_at'])
            ->where('visibility', ListVisibility::Public)
            ->withCount('items')
            ->paginate(12, ['*'], 'lists')
            ->withQueryString();

        $recentReviews = $user->reviews()
            ->select(['id', 'user_id', 'title_id', 'headline', 'body', 'contains_spoilers', 'published_at'])
            ->where('status', ReviewStatus::Published)
            ->with('title:id,name,slug,title_type,release_year')
            ->latest('published_at')
            ->limit(6)
            ->get();

        return view('users.show', [
            'user' => $user,
            'publicWatchlist' => $publicWatchlist,
            'publicLists' => $publicLists,
            'recentReviews' => $recentReviews,
        ]);
    }
}
