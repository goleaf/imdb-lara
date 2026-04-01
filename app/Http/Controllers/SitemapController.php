<?php

namespace App\Http\Controllers;

use App\Enums\ListVisibility;
use App\Enums\TitleType;
use App\Models\Person;
use App\Models\Season;
use App\Models\Title;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        return response()
            ->view('seo.sitemap', [
                'staticRoutes' => [
                    route('public.home'),
                    route('public.discover'),
                    route('public.titles.index'),
                    route('public.movies.index'),
                    route('public.series.index'),
                    route('public.people.index'),
                    route('public.rankings.movies'),
                    route('public.rankings.series'),
                    route('public.trending'),
                    route('public.trailers.latest'),
                    route('public.reviews.latest'),
                    route('public.search'),
                ],
                'titles' => Title::query()
                    ->select(['id', 'slug', 'title_type', 'updated_at'])
                    ->published()
                    ->where('title_type', '!=', TitleType::Episode)
                    ->latest('updated_at')
                    ->get(),
                'episodes' => Title::query()
                    ->select(['id', 'slug', 'updated_at'])
                    ->published()
                    ->where('title_type', TitleType::Episode)
                    ->with('episodeMeta.season:id,series_id,slug', 'episodeMeta.series:id,slug')
                    ->latest('updated_at')
                    ->get(),
                'seasons' => Season::query()
                    ->select(['id', 'series_id', 'slug', 'updated_at'])
                    ->with('series:id,slug')
                    ->latest('updated_at')
                    ->get(),
                'people' => Person::query()
                    ->select(['id', 'slug', 'updated_at'])
                    ->published()
                    ->latest('updated_at')
                    ->get(),
                'profiles' => User::query()
                    ->select(['id', 'username', 'updated_at'])
                    ->whereHas('publicLists')
                    ->latest('updated_at')
                    ->get(),
                'lists' => UserList::query()
                    ->select(['id', 'user_id', 'slug', 'updated_at'])
                    ->where('visibility', ListVisibility::Public)
                    ->where('is_watchlist', false)
                    ->with('user:id,username')
                    ->latest('updated_at')
                    ->get(),
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
