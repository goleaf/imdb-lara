<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminReportsIndexQueryAction;
use App\Enums\ReportStatus;
use App\Livewire\Pages\Concerns\RendersPageView;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ReportsPage extends Component
{
    use RendersPageView;
    use WithPagination;

    #[On('moderation-queue-updated')]
    public function refreshQueue(): void
    {
        // Re-render the queue after a moderation action updates report ordering.
    }

    public function render(BuildAdminReportsIndexQueryAction $buildAdminReportsIndexQuery): View
    {
        return $this->renderPageView('admin.reports.index', [
            'reports' => $buildAdminReportsIndexQuery
                ->handle()
                ->simplePaginate(20)
                ->withQueryString(),
            'reportStatuses' => ReportStatus::cases(),
        ]);
    }
}
