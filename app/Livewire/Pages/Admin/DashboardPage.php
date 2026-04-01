<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\GetDashboardStatsAction;
use App\Livewire\Pages\Concerns\RendersLegacyPage;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DashboardPage extends Component
{
    use RendersLegacyPage;

    public function render(GetDashboardStatsAction $getDashboardStats): View
    {
        return $this->renderLegacyPage('admin.dashboard', [
            'stats' => $getDashboardStats->handle(),
        ]);
    }
}
