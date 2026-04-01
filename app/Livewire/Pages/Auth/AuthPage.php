<?php

namespace App\Livewire\Pages\Auth;

use App\Livewire\Pages\Concerns\RendersPageView;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AuthPage extends Component
{
    use RendersPageView;

    public function render(): View
    {
        if (request()->routeIs('login')) {
            return $this->renderPageView('auth.login');
        }

        return $this->renderPageView('auth.register');
    }
}
