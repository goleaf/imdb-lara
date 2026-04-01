<?php

namespace App\Http\Controllers;

use App\Enums\ReviewStatus;
use App\Enums\TitleType;
use App\Models\Season;
use App\Models\Title;
use Illuminate\Contracts\View\View;

class EpisodeController extends Controller
{
    public function __invoke(Title $series, Season $season, Title $episode): View
    {
        $episode->load([
            'episodeMeta.season:id,series_id,name,slug,season_number',
            'episodeMeta.series:id,name,slug,title_type,release_year',
            'genres:id,name,slug',
            'credits.person:id,name,slug',
            'statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count',
            'mediaAssets',
            'reviews' => fn ($query) => $query
                ->where('status', ReviewStatus::Published)
                ->with('author:id,name,username')
                ->latest('published_at'),
        ]);

        $episodeMeta = $episode->episodeMeta;

        abort_unless(
            $episode->title_type === TitleType::Episode
            && $episodeMeta !== null
            && $episodeMeta->series_id === $series->id
            && $episodeMeta->season_id === $season->id,
            404,
        );

        return view('episodes.show', [
            'series' => $series,
            'season' => $season,
            'episode' => $episode,
        ]);
    }
}
