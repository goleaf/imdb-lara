<?php

namespace App\Livewire\Auth;

use App\Actions\Auth\AuthenticateUserAction;
use App\Livewire\Forms\Auth\LoginUserForm;
use Livewire\Component;

class LoginForm extends Component
{
    public LoginUserForm $form;

    public function login(AuthenticateUserAction $authenticateUser): void
    {
        if (! $authenticateUser->handle($this->form->credentials(), $this->form->remember)) {
            $this->addError('form.email', 'These credentials do not match our records.');

            return;
        }

        $this->redirectRoute('public.discover');
    }

    public function render()
    {
        return view('livewire.auth.login-form');
    }
}
