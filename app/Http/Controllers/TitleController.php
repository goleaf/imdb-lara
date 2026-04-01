<?php

namespace App\Http\Controllers;

use App\Enums\MediaKind;
use App\Enums\ReviewStatus;
use App\Models\Title;
use Illuminate\Contracts\View\View;

class TitleController extends Controller
{
    public function index(): View
    {
        $titles = Title::query()
            ->select(['id', 'name', 'slug', 'title_type', 'release_year', 'plot_outline', 'popularity_rank', 'is_published'])
            ->published()
            ->with([
                'genres:id,name,slug',
                'statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count',
                'mediaAssets' => fn ($query) => $query
                    ->select(['id', 'mediable_type', 'mediable_id', 'kind', 'url', 'alt_text', 'position', 'is_primary'])
                    ->where('kind', MediaKind::Poster)
                    ->orderBy('position')
                    ->limit(1),
            ])
            ->orderBy('name')
            ->simplePaginate(12)
            ->withQueryString();

        return view('titles.index', [
            'titles' => $titles,
        ]);
    }

    public function show(Title $title): View
    {
        $title->load([
            'genres:id,name,slug',
            'companies:id,name,slug,kind,country_code',
            'credits.person:id,name,slug',
            'statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count',
            'mediaAssets',
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
