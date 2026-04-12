<?php

namespace App\Livewire\Admin;

use App\Actions\Admin\UpdateReportStatusAction;
use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\Review;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ReportModerationCard extends Component
{
    use AuthorizesRequests;

    public string $contentAction = 'none';

    #[Locked]
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

    #[Computed]
    public function viewData(): array
    {
        return [
            'reportedReviewTitle' => $this->reportedReviewTitle(),
            'reportStatuses' => ReportStatus::cases(),
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.report-moderation-card', $this->viewData);
    }

    private function reportedReviewTitle(): ?Title
    {
        if (! $this->report->reportable instanceof Review) {
            return null;
        }

        return $this->report->reportable->adminTitle ?? $this->report->reportable->title;
    }
}
