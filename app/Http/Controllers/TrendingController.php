<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class TrendingController extends Controller
{
    public function __invoke(): View
    {
        return view('catalog.browse', [
            'pageTitle' => 'Trending',
            'metaDescription' => 'Browse titles trending on Screenbase by watchlist activity and review momentum.',
            'heading' => 'Trending Now',
            'description' => 'A public feed weighted toward watchlist saves, review volume, and popularity ranking.',
            'breadcrumbs' => [
                ['label' => 'Home', 'href' => route('public.home')],
                ['label' => 'Trending'],
            ],
            'badges' => ['Momentum', 'Watchlists', 'Recent discussion'],
            'actions' => [
                ['label' => 'Latest Reviews', 'href' => route('public.reviews.latest'), 'variant' => 'outline', 'icon' => 'chat-bubble-left-right'],
                ['label' => 'Latest Trailers', 'href' => route('public.trailers.latest'), 'variant' => 'ghost', 'icon' => 'play'],
            ],
            'browserProps' => [
                'sort' => 'trending',
                'pageName' => 'trending',
                'emptyHeading' => 'No trending titles are available yet.',
                'emptyText' => 'As the community adds watchlists and reviews, this page will update.',
            ],
        ]);
    }
}
