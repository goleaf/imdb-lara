<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminContributionsIndexQueryAction;
use App\Enums\ContributionStatus;
use App\Livewire\Pages\Concerns\RendersPageView;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ContributionsPage extends Component
{
    use RendersPageView;
    use WithPagination;

    public function render(BuildAdminContributionsIndexQueryAction $buildAdminContributionsIndexQuery): View
    {
        return $this->renderPageView('admin.contributions.index', [
            'contributions' => $buildAdminContributionsIndexQuery
                ->handle()
                ->simplePaginate(20)
                ->withQueryString(),
            'contributionStatuses' => ContributionStatus::cases(),
        ]);
    }
}
