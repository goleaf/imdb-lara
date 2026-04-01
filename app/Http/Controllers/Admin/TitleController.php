<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\BuildAdminTitlesIndexQueryAction;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class TitleController extends Controller
{
    public function index(BuildAdminTitlesIndexQueryAction $buildAdminTitlesIndexQuery): View
    {
        return view('admin.titles.index', [
            'titles' => $buildAdminTitlesIndexQuery
                ->handle()
                ->simplePaginate(20)
                ->withQueryString(),
        ]);
    }
}
