<?php

namespace App\Http\Controllers;

use App\Enums\TitleType;
use Illuminate\Contracts\View\View;

class MovieController extends Controller
{
    public function __invoke(): View
    {
        return view('catalog.browse', [
            'pageTitle' => 'Browse Movies',
            'metaDescription' => 'Browse published movies on Screenbase with ratings, reviews, and genre links.',
            'heading' => 'Browse Movies',
            'description' => 'Explore released feature films through the existing Screenbase card system, rating aggregates, and public detail pages.',
            'breadcrumbs' => [
                ['label' => 'Home', 'href' => route('public.home')],
                ['label' => 'Movies'],
            ],
            'badges' => ['Feature films', 'Audience ratings', 'Editorial discovery'],
            'actions' => [
                ['label' => 'Top Rated Movies', 'href' => route('public.rankings.movies'), 'variant' => 'outline', 'icon' => 'star'],
                ['label' => 'Trending', 'href' => route('public.trending'), 'variant' => 'ghost', 'icon' => 'bolt'],
            ],
            'browserProps' => [
                'types' => [TitleType::Movie->value],
                'sort' => 'popular',
                'pageName' => 'movies',
                'emptyHeading' => 'No published movies are available right now.',
                'emptyText' => 'Check back soon or explore the broader title catalog.',
            ],
        ]);
    }
}
