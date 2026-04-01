<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\BuildAdminReportsIndexQueryAction;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class ReportController extends Controller
{
    public function index(BuildAdminReportsIndexQueryAction $buildAdminReportsIndexQuery): View
    {
        return view('admin.reports.index', [
            'reports' => $buildAdminReportsIndexQuery
                ->handle()
                ->simplePaginate(20)
                ->withQueryString(),
        ]);
    }
}
