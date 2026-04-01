<?php

namespace App\Http\Controllers;

use App\Actions\Catalog\LoadSeasonDetailsAction;
use App\Http\Requests\Catalog\ShowSeasonRequest;
use App\Models\Season;
use App\Models\Title;
use Illuminate\Contracts\View\View;

class SeasonController extends Controller
{
    public function __invoke(
        ShowSeasonRequest $request,
        Title $series,
        Season $season,
        LoadSeasonDetailsAction $loadSeasonDetails,
    ): View {
        $series = $request->series();
        $season = $request->season();

        return view('seasons.show', $loadSeasonDetails->handle($series, $season, $request->user()));
    }
}
