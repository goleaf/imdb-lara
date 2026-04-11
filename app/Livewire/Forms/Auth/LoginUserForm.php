<?php

namespace App\Livewire\Forms\Auth;

use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginUserForm extends Form
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * @return array{email: string, password: string}
     */
    public function credentials(): array
    {
        $validated = $this->validate();

        return [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];
    }
}
