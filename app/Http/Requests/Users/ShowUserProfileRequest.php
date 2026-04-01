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

        if ($this->integer('lists', 1) > 1) {
            return true;
        }

        return $user->publicLists()->exists() || $user->publicWatchlist()->exists();
    }

    public function profileUser(): User
    {
        /** @var User */
        return $this->route('user');
    }
}
