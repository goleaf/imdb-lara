<?php

namespace App\Http\Controllers;

use App\Enums\TitleType;
use Illuminate\Contracts\View\View;

class TopRatedMovieController extends Controller
{
    public function __invoke(): View
    {
        return view('catalog.browse', [
            'pageTitle' => 'Top Rated Movies',
            'metaDescription' => 'Browse Screenbase movies ordered by rating and rating volume.',
            'heading' => 'Top Rated Movies',
            'description' => 'Feature films ranked by aggregate audience rating, then stabilized by vote volume.',
            'breadcrumbs' => [
                ['label' => 'Home', 'href' => route('public.home')],
                ['label' => 'Top Rated Movies'],
            ],
            'badges' => ['Rating-driven', 'Movie only'],
            'actions' => [
                ['label' => 'Browse Movies', 'href' => route('public.movies.index'), 'variant' => 'outline', 'icon' => 'film'],
                ['label' => 'Latest Reviews', 'href' => route('public.reviews.latest'), 'variant' => 'ghost', 'icon' => 'chat-bubble-left-right'],
            ],
            'browserProps' => [
                'types' => [TitleType::Movie->value],
                'sort' => 'rating',
                'pageName' => 'top-rated-movies',
                'emptyHeading' => 'No rated movies are available yet.',
                'emptyText' => 'As ratings arrive, this page will surface the strongest films.',
            ],
        ]);
    }
}
