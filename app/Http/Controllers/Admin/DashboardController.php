<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Review;
use App\Models\Title;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'titles' => Title::query()->count(),
                'pending_reviews' => Review::query()->where('status', 'pending')->count(),
                'open_reports' => Report::query()->where('status', 'open')->count(),
            ],
        ]);
    }
}
