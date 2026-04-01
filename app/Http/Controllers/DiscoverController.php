<?php

namespace App\Http\Controllers;

use App\MediaKind;
use App\Models\Genre;
use App\Models\Title;
use Illuminate\Contracts\View\View;

class DiscoverController extends Controller
{
    public function __invoke(): View
    {
        return view('discover.index', [
            'featuredGenres' => Genre::query()
                ->select(['id', 'name', 'slug'])
                ->orderBy('name')
                ->get(),
            'featuredTitles' => Title::query()
                ->select(['id', 'name', 'slug', 'title_type', 'release_year', 'plot_outline', 'popularity_rank', 'is_published'])
                ->published()
                ->with([
                    'statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count',
                    'mediaAssets' => fn ($query) => $query
                        ->select(['id', 'mediable_type', 'mediable_id', 'kind', 'url', 'alt_text', 'position', 'is_primary'])
                        ->where('kind', MediaKind::Poster)
                        ->orderBy('position')
                        ->limit(1),
                ])
                ->orderBy('popularity_rank')
                ->limit(3)
                ->get(),
        ]);
    }
}
