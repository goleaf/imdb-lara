<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminReportsIndexQueryAction;
use App\Enums\ReportStatus;
use App\Livewire\Pages\Concerns\RendersLegacyPage;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ReportsPage extends Component
{
    use RendersLegacyPage;
    use WithPagination;

    public function render(BuildAdminReportsIndexQueryAction $buildAdminReportsIndexQuery): View
    {
        return $this->renderLegacyPage('admin.reports.index', [
            'reports' => $buildAdminReportsIndexQuery
                ->handle()
                ->simplePaginate(20)
                ->withQueryString(),
            'reportStatuses' => ReportStatus::cases(),
        ]);
    }
}
