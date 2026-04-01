<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Title;
use Illuminate\Contracts\View\View;

class TitleController extends Controller
{
    public function index(): View
    {
        return view('admin.titles.index', [
            'titles' => Title::query()
                ->select(['id', 'name', 'slug', 'title_type', 'release_year', 'is_published'])
                ->orderBy('name')
                ->simplePaginate(20)
                ->withQueryString(),
        ]);
    }
}
