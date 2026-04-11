<?php

namespace App\Livewire\Admin;

use App\Actions\Admin\UpdateReportStatusAction;
use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ReportModerationCard extends Component
{
    use AuthorizesRequests;

    public string $contentAction = 'none';

    public Report $report;

    public ?string $resolutionNotes = null;

    public string $status = ReportStatus::Open->value;

    public ?string $statusMessage = null;

    public bool $suspendOwner = false;

    public function mount(Report $report): void
    {
        $this->authorize('update', $report);

        $this->report = $report->loadMissing(Report::adminQueueRelations());
        $this->status = $report->status->value;
        $this->resolutionNotes = $report->resolution_notes;
    }

    public function save(UpdateReportStatusAction $updateReportStatus): void
    {
        $this->authorize('update', $this->report);

        $validated = $this->validate([
            'status' => ['required', Rule::enum(ReportStatus::class)],
            'contentAction' => ['nullable', Rule::in(['none', 'hide_content'])],
            'resolutionNotes' => ['nullable', 'string', 'max:2000'],
            'suspendOwner' => ['boolean'],
        ]);

        $this->report = $updateReportStatus
            ->handle(auth()->user(), $this->report, [
                'status' => $validated['status'],
                'content_action' => $validated['contentAction'],
                'resolution_notes' => $validated['resolutionNotes'],
                'suspend_owner' => $validated['suspendOwner'],
            ])
            ->load(Report::adminQueueRelations());

        $this->status = $this->report->status->value;
        $this->resolutionNotes = $this->report->resolution_notes;
        $this->contentAction = 'none';
        $this->suspendOwner = false;
        $this->statusMessage = 'Report moderation saved.';

        $this->dispatch('moderation-queue-updated');
    }

    public function render()
    {
        return view('livewire.admin.report-moderation-card', [
            'reportStatuses' => ReportStatus::cases(),
        ]);
    }
}
