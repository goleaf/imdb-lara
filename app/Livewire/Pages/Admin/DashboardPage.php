<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\GetDashboardStatsAction;
use App\Livewire\Pages\Concerns\RendersPageView;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DashboardPage extends Component
{
    use RendersPageView;

    public function render(GetDashboardStatsAction $getDashboardStats): View
    {
        return $this->renderPageView('admin.dashboard', [
            'stats' => $getDashboardStats->handle(),
        ]);
    }
}
