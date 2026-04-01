<?php

namespace App\Http\Controllers;

use App\Enums\ListVisibility;
use App\Models\Person;
use App\Models\Title;
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
                    route('public.people.index'),
                    route('public.search'),
                ],
                'titles' => Title::query()
                    ->select(['id', 'slug', 'updated_at'])
                    ->published()
                    ->latest('updated_at')
                    ->get(),
                'people' => Person::query()
                    ->select(['id', 'slug', 'updated_at'])
                    ->published()
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
