<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class SearchController extends Controller
{
    public function __invoke(): View
    {
        return view('search.index');
    }
}
