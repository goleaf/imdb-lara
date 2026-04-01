<?php

namespace App\Http\Controllers;

use App\Actions\Catalog\GetFeaturedGenresAction;
use App\Actions\Catalog\GetFeaturedTitlesAction;
use Illuminate\Contracts\View\View;

class DiscoverController extends Controller
{
    public function __invoke(
        GetFeaturedGenresAction $getFeaturedGenres,
        GetFeaturedTitlesAction $getFeaturedTitles,
    ): View {
        return view('discover.index', [
            'featuredGenres' => $getFeaturedGenres->handle(),
            'featuredTitles' => $getFeaturedTitles->handle(3),
        ]);
    }
}
