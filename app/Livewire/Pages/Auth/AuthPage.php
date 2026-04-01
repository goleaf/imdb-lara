<?php

namespace App\Livewire\Pages\Auth;

use App\Livewire\Pages\Concerns\RendersLegacyPage;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AuthPage extends Component
{
    use RendersLegacyPage;

    public function render(): View
    {
        if (request()->routeIs('login')) {
            return $this->renderLegacyPage('auth.login');
        }

        return $this->renderLegacyPage('auth.register');
    }
}
