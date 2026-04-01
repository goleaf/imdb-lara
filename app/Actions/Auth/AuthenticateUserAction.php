<?php

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Auth;

class AuthenticateUserAction
{
    /**
     * @param  array{email: string, password: string}  $credentials
     */
    public function handle(array $credentials, bool $remember = false): bool
    {
        if (! Auth::attempt($credentials, $remember)) {
            return false;
        }

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        return true;
    }
}
