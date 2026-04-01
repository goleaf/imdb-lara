<?php

namespace App\Actions\Seo;

use App\Enums\ListVisibility;
use App\Enums\ProfileVisibility;
use App\Enums\TitleType;
use App\Models\Genre;
use App\Models\Person;
use App\Models\Season;
use App\Models\Title;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class GetSitemapDataAction
{
    /**
     * @return array{
     *     staticRoutes: list<string>,
     *     genres: EloquentCollection<int, Genre>,
     *     years: Collection<int, int>,
     *     titles: EloquentCollection<int, Title>,
     *     episodes: EloquentCollection<int, Title>,
     *     seasons: EloquentCollection<int, Season>,
     *     people: EloquentCollection<int, Person>,
     *     profiles: EloquentCollection<int, User>,
     *     lists: EloquentCollection<int, UserList>
     * }
     */
    public function handle(): array
    {
        $staticRoutes = collect([
            'public.home',
            'public.discover',
            'public.titles.index',
            'public.people.index',
            'public.search',
            'public.movies.index',
            'public.series.index',
            'public.rankings.movies',
            'public.rankings.series',
            'public.trending',
            'public.trailers.latest',
            'public.reviews.latest',
        ])
            ->filter(fn (string $routeName): bool => Route::has($routeName))
            ->map(fn (string $routeName): string => route($routeName))
            ->values()
            ->all();

        return [
            'staticRoutes' => $staticRoutes,
            'genres' => Genre::query()
                ->select(['id', 'slug', 'updated_at'])
                ->whereHas('titles', fn ($query) => $query->publishedCatalog())
                ->orderBy('slug')
                ->get(),
            'years' => Title::query()
                ->select(['release_year'])
                ->publishedCatalog()
                ->whereNotNull('release_year')
                ->distinct()
                ->orderByDesc('release_year')
                ->pluck('release_year'),
            'titles' => Title::query()
                ->select(['id', 'slug', 'updated_at'])
                ->publishedCatalog()
                ->latest('updated_at')
                ->get(),
            'episodes' => Route::has('public.episodes.show')
                ? Title::query()
                    ->select(['id', 'slug', 'updated_at'])
                    ->published()
                    ->where('title_type', TitleType::Episode)
                    ->with('episodeMeta.season:id,series_id,slug', 'episodeMeta.series:id,slug')
                    ->latest('updated_at')
                    ->get()
                : new EloquentCollection,
            'seasons' => Route::has('public.seasons.show')
                ? Season::query()
                    ->select(['id', 'series_id', 'slug', 'updated_at'])
                    ->with('series:id,slug')
                    ->latest('updated_at')
                    ->get()
                : new EloquentCollection,
            'people' => Person::query()
                ->select(['id', 'slug', 'updated_at'])
                ->published()
                ->latest('updated_at')
                ->get(),
            'profiles' => Route::has('public.users.show')
                ? User::query()
                    ->select(['id', 'username', 'updated_at'])
                    ->where('profile_visibility', ProfileVisibility::Public)
                    ->where(function ($query): void {
                        $query
                            ->whereHas('publicLists')
                            ->orWhereHas('publicWatchlist')
                            ->orWhereHas('reviews', fn ($reviewQuery) => $reviewQuery->published())
                            ->orWhere(function ($ratingsQuery): void {
                                $ratingsQuery
                                    ->where('show_ratings_on_profile', true)
                                    ->whereHas('ratings');
                            });
                    })
                    ->latest('updated_at')
                    ->get()
                : new EloquentCollection,
            'lists' => UserList::query()
                ->select(['id', 'user_id', 'slug', 'updated_at'])
                ->where('visibility', ListVisibility::Public)
                ->where('is_watchlist', false)
                ->with('user:id,username')
                ->latest('updated_at')
                ->get(),
        ];
    }
}
