<?php

namespace App\Http\Controllers;

use App\Enums\TitleType;
use App\Models\Season;
use App\Models\Title;
use Illuminate\Contracts\View\View;

class SeasonController extends Controller
{
    public function __invoke(Title $series, Season $season): View
    {
        abort_unless(
            in_array($series->title_type, [TitleType::Series, TitleType::MiniSeries], true)
            && $season->series_id === $series->id,
            404,
        );

        $season->load([
            'series:id,name,slug,title_type,release_year,plot_outline',
            'episodes' => fn ($query) => $query
                ->select(['id', 'season_id', 'series_id', 'title_id', 'episode_number', 'season_number', 'aired_at'])
                ->with([
                    'title:id,name,slug,title_type,release_year,plot_outline',
                    'title.statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count',
                ]),
        ]);

        return view('seasons.show', [
            'series' => $series,
            'season' => $season,
        ]);
    }
}
