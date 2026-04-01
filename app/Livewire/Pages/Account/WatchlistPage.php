<?php

namespace App\Livewire\Pages\Account;

use App\Livewire\Pages\Concerns\RendersLegacyPage;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class WatchlistPage extends Component
{
    use RendersLegacyPage;

    public function render(): View
    {
        return $this->renderLegacyPage('account.watchlist');
    }
}
