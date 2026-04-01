<?php

namespace App\Http\Controllers;

use App\Enums\TitleType;
use Illuminate\Contracts\View\View;

class SeriesController extends Controller
{
    public function __invoke(): View
    {
        return view('catalog.browse', [
            'pageTitle' => 'Browse TV Shows',
            'metaDescription' => 'Browse published TV series and mini-series on Screenbase.',
            'heading' => 'Browse TV Shows',
            'description' => 'Track ongoing series, mini-series, and season structures with the same catalog, review, and people architecture used across the public site.',
            'breadcrumbs' => [
                ['label' => 'Home', 'href' => route('public.home')],
                ['label' => 'TV Shows'],
            ],
            'badges' => ['Series', 'Mini-series', 'Season-aware pages'],
            'actions' => [
                ['label' => 'Top Rated Series', 'href' => route('public.rankings.series'), 'variant' => 'outline', 'icon' => 'star'],
                ['label' => 'Latest Trailers', 'href' => route('public.trailers.latest'), 'variant' => 'ghost', 'icon' => 'play'],
            ],
            'browserProps' => [
                'types' => [TitleType::Series->value, TitleType::MiniSeries->value],
                'sort' => 'popular',
                'pageName' => 'tv-shows',
                'emptyHeading' => 'No published TV shows are available right now.',
                'emptyText' => 'Check back soon or explore other areas of the catalog.',
            ],
        ]);
    }
}
