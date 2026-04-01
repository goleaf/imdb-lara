<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\GetDashboardStatsAction;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(GetDashboardStatsAction $getDashboardStats): View
    {
        return view('admin.dashboard', [
            'stats' => $getDashboardStats->handle(),
        ]);
    }
}
