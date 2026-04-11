<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LogoutButton extends Component
{
    public string $buttonClass = '';

    public string $icon = 'arrow-right-start-on-rectangle';

    public string $label = 'Sign out';

    public string $presentation = 'button';

    public string $size = 'md';

    public string $variant = 'danger';

    public function logout(): void
    {
        Auth::guard('web')->logout();

        if (request()->hasSession()) {
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        $this->redirectRoute('public.home');
    }

    public function render()
    {
        return view('livewire.auth.logout-button');
    }
}
