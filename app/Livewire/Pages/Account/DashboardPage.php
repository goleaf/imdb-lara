<?php

namespace App\Livewire\Pages\Account;

use App\Actions\Users\LoadAccountDashboardAction;
use App\Livewire\Pages\Concerns\RendersLegacyPage;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DashboardPage extends Component
{
    use RendersLegacyPage;

    public function render(LoadAccountDashboardAction $loadAccountDashboard): View
    {
        return $this->renderLegacyPage('account.dashboard', $loadAccountDashboard->handle(auth()->user()));
    }
}
