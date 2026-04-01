<?php

namespace App\Http\Controllers;

use App\Actions\Home\GetLatestTrailerTitlesAction;
use Illuminate\Contracts\View\View;

class LatestTrailerController extends Controller
{
    public function __invoke(GetLatestTrailerTitlesAction $getLatestTrailerTitles): View
    {
        $titles = $getLatestTrailerTitles
            ->query()
            ->simplePaginate(12)
            ->withQueryString();

        return view('trailers.index', [
            'titles' => $titles,
        ]);
    }
}
