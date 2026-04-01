<?php

namespace App\Http\Controllers;

use App\Actions\Catalog\LoadEpisodeDetailsAction;
use App\Http\Requests\Catalog\ShowEpisodeRequest;
use App\Models\Season;
use App\Models\Title;
use Illuminate\Contracts\View\View;

class EpisodeController extends Controller
{
    public function __invoke(
        ShowEpisodeRequest $request,
        Title $series,
        Season $season,
        Title $episode,
        LoadEpisodeDetailsAction $loadEpisodeDetails,
    ): View {
        $series = $request->series();
        $season = $request->season();
        $episode = $request->episode();

        return view('episodes.show', $loadEpisodeDetails->handle($series, $season, $episode));
    }
}
