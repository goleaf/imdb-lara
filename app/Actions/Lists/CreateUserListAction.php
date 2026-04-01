<?php

namespace App\Actions\Lists;

use App\ListVisibility;
use App\Models\User;
use App\Models\UserList;

class CreateUserListAction
{
    /**
     * @param  array{name: string, description?: string|null, visibility: string}  $attributes
     */
    public function handle(User $user, array $attributes): UserList
    {
        return UserList::query()->create([
            'user_id' => $user->id,
            'name' => $attributes['name'],
            'description' => $attributes['description'] ?? null,
            'visibility' => ListVisibility::from($attributes['visibility']),
            'is_watchlist' => false,
        ]);
    }
}
