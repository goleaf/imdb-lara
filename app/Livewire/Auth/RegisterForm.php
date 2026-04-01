<?php

namespace App\Livewire\Auth;

use App\Actions\Auth\RegisterUserAction;
use App\Livewire\Forms\Auth\RegisterUserForm;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RegisterForm extends Component
{
    public RegisterUserForm $form;

    public function register(RegisterUserAction $registerUser): void
    {
        $user = $registerUser->handle($this->form->payload());

        Auth::login($user);

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        $this->redirectRoute('public.discover');
    }

    public function render()
    {
        return view('livewire.auth.register-form');
    }
}
