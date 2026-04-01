<?php

namespace App\Actions\Users;

use App\Actions\Seo\PageSeoData;
use App\Enums\ListVisibility;
use App\Models\Rating;
use App\Models\Review;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class LoadPublicUserProfileAction
{
    public function __construct(
        protected BuildPublicUserRatingsQueryAction $buildPublicUserRatingsQuery,
        protected BuildPublicUserReviewsQueryAction $buildPublicUserReviewsQuery,
    ) {}

    /**
     * @return array{
     *     user: User,
     *     publicWatchlist: ?UserList,
     *     publicLists: LengthAwarePaginator,
     *     recentRatings: EloquentCollection<int, Rating>,
     *     recentReviews: EloquentCollection<int, Review>
     * }
     */
    public function handle(User $profileUser, ?User $viewer = null): array
    {
        $user = User::query()
            ->select([
                'id',
                'name',
                'username',
                'bio',
                'avatar_path',
                'created_at',
                'profile_visibility',
                'show_ratings_on_profile',
            ])
            ->findOrFail($profileUser->id);

        $user->loadCount([
            'publicLists',
            'reviews as published_reviews_count' => fn ($query) => $query->published(),
            'ratings',
        ]);

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
            ->where('visibility', ListVisibility::Public->value)
            ->withCount('items')
            ->paginate(12, ['*'], 'lists')
            ->withQueryString();

        $recentReviews = $this->buildPublicUserReviewsQuery
            ->handle($user, $viewer)
            ->limit(6)
            ->get();

        $recentRatings = $user->showsRatingsOnProfile()
            ? $this->buildPublicUserRatingsQuery->handle($user)->limit(8)->get()
            : new EloquentCollection;

        return [
            'user' => $user,
            'publicWatchlist' => $publicWatchlist,
            'publicLists' => $publicLists,
            'recentRatings' => $recentRatings,
            'recentReviews' => $recentReviews,
            'seo' => new PageSeoData(
                title: $user->name,
                description: $user->bio ?: 'Browse public ratings, reviews, watchlists, and custom lists from '.$user->name.'.',
                canonical: route('public.users.show', $user),
                openGraphType: 'profile',
                openGraphImage: $user->avatar_url,
                openGraphImageAlt: $user->name,
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => $user->name],
                ],
                paginationPageName: 'lists',
            ),
        ];
    }
}
