<?php

namespace App\Http\Controllers;

use App\MediaKind;
use App\Models\Title;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $featuredTitles = Title::query()
            ->select([
                'id',
                'name',
                'slug',
                'title_type',
                'release_year',
                'plot_outline',
                'popularity_rank',
                'is_published',
            ])
            ->published()
            ->with([
                'statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count',
                'genres:id,name,slug',
                'mediaAssets' => fn ($query) => $query
                    ->select(['id', 'mediable_type', 'mediable_id', 'kind', 'url', 'alt_text', 'position', 'is_primary'])
                    ->where('kind', MediaKind::Poster)
                    ->orderBy('position')
                    ->limit(1),
            ])
            ->orderBy('popularity_rank')
            ->limit(6)
            ->get();

        return view('home', [
            'featuredTitles' => $featuredTitles,
        ]);
    }
}
