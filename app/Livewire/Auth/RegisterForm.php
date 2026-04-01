<?php

namespace App\Livewire\Auth;

use App\Actions\Auth\RegisterUserAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class RegisterForm extends Component
{
    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', 'min:8'],
        ];
    }

    public function register(RegisterUserAction $registerUser): void
    {
        $validated = $this->validate();

        $user = $registerUser->handle($validated);

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
