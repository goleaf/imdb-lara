<?php

namespace App\Http\Controllers;

use App\Enums\TitleType;
use Illuminate\Contracts\View\View;

class TopRatedSeriesController extends Controller
{
    public function __invoke(): View
    {
        return view('catalog.browse', [
            'pageTitle' => 'Top Rated Series',
            'metaDescription' => 'Browse Screenbase series and mini-series ordered by audience rating.',
            'heading' => 'Top Rated Series',
            'description' => 'TV and mini-series ranked by audience score with review and watchlist momentum alongside each record.',
            'breadcrumbs' => [
                ['label' => 'Home', 'href' => route('public.home')],
                ['label' => 'Top Rated Series'],
            ],
            'badges' => ['TV and mini-series', 'Rating-driven'],
            'actions' => [
                ['label' => 'Browse TV Shows', 'href' => route('public.series.index'), 'variant' => 'outline', 'icon' => 'tv'],
                ['label' => 'Latest Trailers', 'href' => route('public.trailers.latest'), 'variant' => 'ghost', 'icon' => 'play'],
            ],
            'browserProps' => [
                'types' => [TitleType::Series->value, TitleType::MiniSeries->value],
                'sort' => 'rating',
                'pageName' => 'top-rated-series',
                'emptyHeading' => 'No rated series are available yet.',
                'emptyText' => 'Once ratings accumulate, this page will rank the strongest series.',
            ],
        ]);
    }
}
