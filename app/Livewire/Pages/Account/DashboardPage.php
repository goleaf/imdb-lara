<?php

namespace App\Livewire\Pages\Account;

use App\Actions\Users\LoadAccountDashboardAction;
use App\Livewire\Pages\Concerns\RendersPageView;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DashboardPage extends Component
{
    use RendersPageView;

    public function render(LoadAccountDashboardAction $loadAccountDashboard): View
    {
        return $this->renderPageView('account.dashboard', $loadAccountDashboard->handle(auth()->user()));
    }
}
