<?php

namespace App\Livewire\Forms\Auth;

use Livewire\Attributes\Validate;
use Livewire\Form;

class RegisterUserForm extends Form
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255|unique:users,username')]
    public string $username = '';

    #[Validate('required|email|max:255|unique:users,email')]
    public string $email = '';

    #[Validate('required|confirmed|min:8')]
    public string $password = '';

    #[Validate('required')]
    public string $password_confirmation = '';

    /**
     * @return array{name: string, username: string, email: string, password: string}
     */
    public function payload(): array
    {
        $validated = $this->validate();

        return [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];
    }
}
