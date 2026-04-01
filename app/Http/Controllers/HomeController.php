<?php

namespace App\Http\Controllers;

use App\Actions\Home\GetHeroSpotlightAction;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(GetHeroSpotlightAction $getHeroSpotlight): View
    {
        return view('home', [
            'heroSpotlight' => $getHeroSpotlight->handle(),
        ]);
    }
}
