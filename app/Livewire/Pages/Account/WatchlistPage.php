<?php

namespace App\Livewire\Pages\Account;

use App\Livewire\Pages\Concerns\RendersPageView;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class WatchlistPage extends Component
{
    use RendersPageView;

    public function render(): View
    {
        return $this->renderPageView('account.watchlist');
    }
}
