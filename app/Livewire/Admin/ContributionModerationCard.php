<?php

namespace App\Livewire\Admin;

use App\Actions\Admin\UpdateContributionStatusAction;
use App\Enums\ContributionStatus;
use App\Models\Contribution;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ContributionModerationCard extends Component
{
    use AuthorizesRequests;

    public Contribution $contribution;

    public ?string $notes = null;

    public string $status = ContributionStatus::Submitted->value;

    public ?string $statusMessage = null;

    public function mount(Contribution $contribution): void
    {
        $this->authorize('update', $contribution);

        $this->contribution = $contribution;
        $this->status = $contribution->status->value;
        $this->notes = $contribution->review_notes;
    }

    public function save(UpdateContributionStatusAction $updateContributionStatus): void
    {
        $this->authorize('update', $this->contribution);

        $validated = $this->validate([
            'status' => ['required', Rule::enum(ContributionStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->contribution = $updateContributionStatus
            ->handle(auth()->user(), $this->contribution, $validated)
            ->load([
                'user:id,name,username',
                'reviewer:id,name,username',
                'contributable',
            ]);

        $this->status = $this->contribution->status->value;
        $this->notes = $this->contribution->review_notes;
        $this->statusMessage = 'Contribution review saved.';

        $this->dispatch('moderation-queue-updated');
    }

    public function render()
    {
        return view('livewire.admin.contribution-moderation-card', [
            'contributionStatuses' => ContributionStatus::cases(),
        ]);
    }
}
