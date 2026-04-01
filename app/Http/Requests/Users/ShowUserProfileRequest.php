<?php

namespace App\Http\Requests\Users;

use App\Http\Requests\NotFoundFormRequest;
use App\Models\User;

class ShowUserProfileRequest extends NotFoundFormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        if (! $user instanceof User) {
            return false;
        }

        return $user->hasVisibleProfileContent();
    }

    public function profileUser(): User
    {
        /** @var User */
        return $this->route('user');
    }
}
