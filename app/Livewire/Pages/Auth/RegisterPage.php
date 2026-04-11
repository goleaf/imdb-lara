<?php

namespace App\Livewire\Pages\Auth;

use Illuminate\Contracts\View\View;

class RegisterPage extends AuthPage
{
    public function render(): View
    {
        return $this->renderAuthPage('auth.register');
    }
}
