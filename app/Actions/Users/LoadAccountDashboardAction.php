<?php

namespace App\Actions\Users;

use App\Actions\Lists\EnsureWatchlistAction;
use App\Enums\WatchState;
use App\Models\ListItem;
use App\Models\Rating;
use App\Models\Review;
use App\Models\Title;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LoadAccountDashboardAction
{
    public function __construct(
        protected EnsureWatchlistAction $ensureWatchlist,
    ) {}

    /**
     * @return array{
     *     user: User,
     *     watchlist: UserList,
     *     watchlistPreviewItems: EloquentCollection<int, ListItem>,
     *     recentRatings: EloquentCollection<int, Rating>,
     *     recentReviews: EloquentCollection<int, Review>,
     *     quickLinks: EloquentCollection<int, UserList>,
     *     recentActivity: Collection<int, array{
     *         icon: string,
     *         label: string,
     *         meta: string,
     *         occurred_at: Carbon,
     *         href: string
     *     }>,
     *     publicProfileIsLive: bool
     * }
     */
    public function handle(User $accountUser): array
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
            ->findOrFail($accountUser->id);

        $user->loadCount([
            'ratings',
            'customLists',
            'publicLists',
            'reviews as published_reviews_count' => fn ($query) => $query->published(),
        ]);

        $watchlist = $this->ensureWatchlist->handle($user);

        $watchlist->loadCount([
            'items',
            'items as watched_items_count' => fn ($query) => $query->where('watch_state', WatchState::Completed),
            'items as planned_items_count' => fn ($query) => $query->where('watch_state', WatchState::Planned),
            'items as watching_items_count' => fn ($query) => $query->where('watch_state', WatchState::Watching),
        ]);

        $watchlistPreviewItems = $watchlist->items()
            ->select(['id', 'user_list_id', 'title_id', 'watch_state', 'created_at', 'watched_at'])
            ->latest('created_at')
            ->limit(4)
            ->with([
                'title' => fn ($titleQuery) => $titleQuery
                    ->select(Title::catalogCardColumns())
                    ->publishedCatalog()
                    ->withCatalogCardRelations(),
            ])
            ->get();

        $recentRatings = Rating::query()
            ->select(['id', 'user_id', 'title_id', 'score', 'created_at'])
            ->whereBelongsTo($user)
            ->with([
                'title' => fn ($titleQuery) => $titleQuery
                    ->select(Title::catalogCardColumns())
                    ->publishedCatalog()
                    ->withCatalogCardRelations(),
            ])
            ->latest('created_at')
            ->limit(5)
            ->get();

        $recentReviews = Review::query()
            ->select([
                'id',
                'user_id',
                'title_id',
                'headline',
                'body',
                'status',
                'published_at',
                'created_at',
            ])
            ->authoredBy($user)
            ->with([
                'title' => fn ($titleQuery) => $titleQuery
                    ->select(Title::catalogCardColumns())
                    ->publishedCatalog()
                    ->withCatalogCardRelations(),
            ])
            ->latest('published_at')
            ->latest('created_at')
            ->limit(5)
            ->get();

        $quickLinks = UserList::query()
            ->select([
                'id',
                'user_id',
                'name',
                'slug',
                'visibility',
                'is_watchlist',
                'updated_at',
            ])
            ->whereBelongsTo($user)
            ->withCount('items')
            ->latest('updated_at')
            ->limit(6)
            ->get();

        $recentActivity = collect()
            ->merge($recentRatings->map(fn (Rating $rating): array => [
                'icon' => 'star',
                'label' => sprintf('Rated %s', $rating->title->name),
                'meta' => sprintf('%d/10', $rating->score),
                'occurred_at' => $rating->created_at,
                'href' => route('public.titles.show', $rating->title),
            ]))
            ->merge($recentReviews->map(fn (Review $review): array => [
                'icon' => 'chat-bubble-left-right',
                'label' => sprintf('Reviewed %s', $review->title->name),
                'meta' => $review->status->value === 'published'
                    ? 'Published review'
                    : (string) str($review->status->value)->headline(),
                'occurred_at' => $review->published_at ?? $review->created_at,
                'href' => route('public.titles.show', $review->title).'#reviews',
            ]))
            ->merge($watchlistPreviewItems->map(fn ($item): array => [
                'icon' => 'bookmark',
                'label' => sprintf('Saved %s', $item->title->name),
                'meta' => $item->watch_state ? (string) str($item->watch_state->value)->headline() : 'Planned',
                'occurred_at' => $item->created_at,
                'href' => route('public.titles.show', $item->title),
            ]))
            ->sortByDesc('occurred_at')
            ->take(8)
            ->values();

        return [
            'user' => $user,
            'watchlist' => $watchlist,
            'watchlistPreviewItems' => $watchlistPreviewItems,
            'recentRatings' => $recentRatings,
            'recentReviews' => $recentReviews,
            'quickLinks' => $quickLinks,
            'recentActivity' => $recentActivity,
            'publicProfileIsLive' => $user->isProfileVisibleToPublic(),
        ];
    }
}
