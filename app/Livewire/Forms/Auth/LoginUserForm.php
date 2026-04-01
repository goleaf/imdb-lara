<?php

namespace App\Livewire\Forms\Auth;

use Livewire\Form;

class LoginUserForm extends Form
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
