<?php

namespace App\Http\Controllers;

use App\Actions\Catalog\LoadTitleDetailsAction;
use App\Enums\TitleType;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

class TitleController extends Controller
{
    public function index(): View
    {
        return view('catalog.browse', [
            'pageTitle' => 'Browse Titles',
            'metaDescription' => 'Browse published Screenbase titles across movies, series, documentaries, shorts, and specials.',
            'heading' => 'Browse Titles',
            'description' => 'The full public title directory, excluding episode records from the main grid so series navigation stays canonical.',
            'breadcrumbs' => [
                ['label' => 'Home', 'href' => route('public.home')],
                ['label' => 'Browse Titles'],
            ],
            'badges' => ['Movies', 'Series', 'Documentaries', 'Specials'],
            'actions' => array_values(array_filter([
                Route::has('public.movies.index')
                    ? ['label' => 'Browse Movies', 'href' => route('public.movies.index'), 'variant' => 'outline', 'icon' => 'film']
                    : null,
                Route::has('public.series.index')
                    ? ['label' => 'Browse TV Shows', 'href' => route('public.series.index'), 'variant' => 'ghost', 'icon' => 'tv']
                    : null,
            ])),
            'browserProps' => [
                'sort' => 'name',
                'pageName' => 'titles',
                'emptyHeading' => 'No published titles match the current catalog.',
                'emptyText' => 'Check back soon or browse discovery and search instead.',
            ],
        ]);
    }

    public function show(Title $title, LoadTitleDetailsAction $loadTitleDetails): View|RedirectResponse
    {
        if ($title->title_type === TitleType::Episode) {
            $title->load('episodeMeta.season:id,series_id,slug', 'episodeMeta.series:id,slug');

            if (
                Route::has('public.episodes.show')
                && $title->episodeMeta?->season
                && $title->episodeMeta?->series
            ) {
                return redirect()->route('public.episodes.show', [
                    'series' => $title->episodeMeta->series,
                    'season' => $title->episodeMeta->season,
                    'episode' => $title,
                ]);
            }
        }

        return view('titles.show', [
            'title' => $loadTitleDetails->handle($title),
        ]);
    }
}
