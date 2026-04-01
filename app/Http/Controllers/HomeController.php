<?php

namespace App\Http\Controllers;

use App\Actions\Catalog\GetFeaturedTitlesAction;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(GetFeaturedTitlesAction $getFeaturedTitles): View
    {
        return view('home', [
            'featuredTitles' => $getFeaturedTitles->handle(),
        ]);
    }
}
