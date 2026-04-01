<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LoginForm extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    protected function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function login(): void
    {
        $this->validate();

        if (! Auth::attempt([
            'email' => $this->email,
            'password' => $this->password,
        ], $this->remember)) {
            $this->addError('email', 'These credentials do not match our records.');

            return;
        }

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        $this->redirectRoute('public.discover');
    }

    public function render()
    {
        return view('livewire.auth.login-form');
    }
}
