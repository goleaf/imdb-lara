<?php

namespace App\Livewire\Forms\Auth;

use Illuminate\Validation\Rule;
use Livewire\Form;

class RegisterUserForm extends Form
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
