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
            'publicLists' => $publicLists,
            'recentReviews' => $recentReviews,
        ]);
    }
}
