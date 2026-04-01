<?php

namespace App\Http\Controllers;

use App\Enums\MediaKind;
use App\Enums\ReviewStatus;
use App\Enums\TitleType;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

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
            'actions' => [
                ['label' => 'Browse Movies', 'href' => route('public.movies.index'), 'variant' => 'outline', 'icon' => 'film'],
                ['label' => 'Browse TV Shows', 'href' => route('public.series.index'), 'variant' => 'ghost', 'icon' => 'tv'],
            ],
            'browserProps' => [
                'sort' => 'name',
                'pageName' => 'titles',
                'emptyHeading' => 'No published titles match the current catalog.',
                'emptyText' => 'Check back soon or browse discovery and search instead.',
            ],
        ]);
    }

    public function show(Title $title): View|RedirectResponse
    {
        if ($title->title_type === TitleType::Episode) {
            $title->load('episodeMeta.season:id,series_id,slug', 'episodeMeta.series:id,slug');

            if ($title->episodeMeta?->season && $title->episodeMeta?->series) {
                return redirect()->route('public.episodes.show', [
                    'series' => $title->episodeMeta->series,
                    'season' => $title->episodeMeta->season,
                    'episode' => $title,
                ]);
            }
        }

        $title->load([
            'genres:id,name,slug',
            'companies:id,name,slug,kind,country_code',
            'credits.person:id,name,slug',
            'statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count',
            'mediaAssets',
            'titleVideos' => fn ($query) => $query
                ->select(['id', 'mediable_type', 'mediable_id', 'kind', 'url', 'caption', 'published_at'])
                ->whereIn('kind', [MediaKind::Trailer, MediaKind::Clip, MediaKind::Featurette])
                ->orderByDesc('published_at')
                ->limit(3),
            'seasons' => fn ($query) => $query
                ->select(['id', 'series_id', 'name', 'slug', 'season_number', 'summary', 'release_year'])
                ->withCount('episodes')
                ->orderBy('season_number'),
            'reviews' => fn ($query) => $query
                ->where('status', ReviewStatus::Published)
                ->with('author:id,name,username')
                ->latest('published_at'),
        ]);

        return view('titles.show', [
            'title' => $title,
        ]);
    }
}
