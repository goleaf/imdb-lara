<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Contracts\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('admin.reports.index', [
            'reports' => Report::query()
                ->select(['id', 'user_id', 'reportable_type', 'reportable_id', 'reason', 'status', 'reviewed_at'])
                ->with('reporter:id,name,username')
                ->latest()
                ->simplePaginate(20)
                ->withQueryString(),
        ]);
    }
}
