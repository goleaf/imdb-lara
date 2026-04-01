<?php

namespace App\Http\Controllers;

use App\Http\Requests\Catalog\ShowYearRequest;
use Illuminate\Contracts\View\View;

class YearController extends Controller
{
    public function __invoke(ShowYearRequest $request): View
    {
        $year = $request->year();

        return view('catalog.browse', [
            'pageTitle' => 'Titles from '.$year,
            'metaDescription' => 'Browse public title pages released in '.$year.' on Screenbase.',
            'heading' => (string) $year,
            'description' => 'Released titles from '.$year.', including movies, series, documentaries, specials, and shorts.',
            'breadcrumbs' => [
                ['label' => 'Home', 'href' => route('public.home')],
                ['label' => 'All Titles', 'href' => route('public.titles.index')],
                ['label' => (string) $year],
            ],
            'badges' => ['Year archive', 'SEO landing page'],
            'actions' => [
                ['label' => 'Trending', 'href' => route('public.trending'), 'variant' => 'outline', 'icon' => 'bolt'],
                ['label' => 'Browse All Titles', 'href' => route('public.titles.index'), 'variant' => 'ghost', 'icon' => 'film'],
            ],
            'browserProps' => [
                'year' => $year,
                'sort' => 'rating',
                'pageName' => 'year-'.$year,
                'emptyHeading' => 'No published titles were found for '.$year.'.',
                'emptyText' => 'Try a different year or browse the broader catalog.',
            ],
        ]);
    }
}
