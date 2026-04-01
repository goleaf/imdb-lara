<?php

namespace App\Actions\Lists;

use App\Enums\ListVisibility;
use App\Models\UserList;

class UpdateUserListAction
{
    /**
     * @param  array{name: string, description?: string|null, visibility: string}  $attributes
     */
    public function handle(UserList $list, array $attributes): UserList
    {
        $list->forceFill([
            'name' => $attributes['name'],
            'description' => $attributes['description'] ?? null,
            'visibility' => ListVisibility::from($attributes['visibility']),
        ])->save();

        return $list->refresh();
    }
}
