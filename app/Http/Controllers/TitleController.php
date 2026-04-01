<?php

namespace App\Http\Controllers;

use App\Actions\Catalog\BuildTitleCreditsQueryAction;
use App\Actions\Catalog\LoadTitleDetailsAction;
use App\Enums\TitleType;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Exceptions\HttpResponseException;
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
        $this->ensurePubliclyVisible($title);

        if ($redirectResponse = $this->redirectCanonicalEpisode($title)) {
            return $redirectResponse;
        }

        return view('titles.show', $loadTitleDetails->handle($title));
    }

    public function cast(Title $title, BuildTitleCreditsQueryAction $buildTitleCreditsQuery): View|RedirectResponse
    {
        $this->ensurePubliclyVisible($title);

        if ($redirectResponse = $this->redirectCanonicalEpisode($title)) {
            return $redirectResponse;
        }

        $title->load([
            'genres:id,name,slug',
            'statistic:id,title_id,rating_count,average_rating,review_count',
            'mediaAssets:id,mediable_type,mediable_id,kind,url,alt_text,position',
        ]);

        $creditsQuery = $buildTitleCreditsQuery->handle($title);
        $castCredits = (clone $creditsQuery)
            ->where('department', 'Cast')
            ->simplePaginate(24, ['*'], 'castPage')
            ->withQueryString();
        $crewCredits = (clone $creditsQuery)
            ->where('department', '!=', 'Cast')
            ->simplePaginate(24, ['*'], 'crewPage')
            ->withQueryString();

        return view('titles.cast', [
            'title' => $title,
            'castCredits' => $castCredits,
            'crewCredits' => $crewCredits,
            'castCount' => (clone $creditsQuery)->where('department', 'Cast')->count(),
            'crewCount' => (clone $creditsQuery)->where('department', '!=', 'Cast')->count(),
        ]);
    }

    private function ensurePubliclyVisible(Title $title): void
    {
        if ($title->is_published) {
            return;
        }

        if (auth()->user()?->can('view', $title)) {
            return;
        }

        throw new HttpResponseException(abort(404));
    }

    private function redirectCanonicalEpisode(Title $title): ?RedirectResponse
    {
        if ($title->title_type !== TitleType::Episode) {
            return null;
        }

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

        return redirect()->route('public.titles.show', $title);
    }
}
