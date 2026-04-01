<?php

namespace App\Actions\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;

class RegisterUserAction
{
    /**
     * @param  array{name: string, username: string, email: string, password: string}  $attributes
     */
    public function handle(array $attributes): User
    {
        return User::query()->create([
            'name' => $attributes['name'],
            'username' => $attributes['username'],
            'email' => $attributes['email'],
            'password' => $attributes['password'],
            'role' => UserRole::RegularUser,
            'status' => UserStatus::Active,
        ]);
    }
}
