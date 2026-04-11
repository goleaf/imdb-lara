<?php

namespace App\Livewire\Pages\Auth;

use Illuminate\Contracts\View\View;

class LoginPage extends AuthPage
{
    public function render(): View
    {
        return $this->renderAuthPage('auth.login');
    }
}
