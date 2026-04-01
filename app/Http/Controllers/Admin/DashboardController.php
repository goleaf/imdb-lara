<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\GetDashboardStatsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShowDashboardRequest;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        ShowDashboardRequest $request,
        GetDashboardStatsAction $getDashboardStats,
    ): View {
        return view('admin.dashboard', [
            'stats' => $getDashboardStats->handle(),
        ]);
    }
}
