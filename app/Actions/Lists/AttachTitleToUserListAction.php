<?php

namespace App\Actions\Lists;

use App\Models\ListItem;
use App\Models\Title;
use App\Models\UserList;

class AttachTitleToUserListAction
{
    public function handle(UserList $list, Title $title): ListItem
    {
        /** @var ListItem|null $existingItem */
        $existingItem = $list->items()
            ->select(['id', 'user_list_id', 'title_id', 'position'])
            ->where('title_id', $title->id)
            ->first();

        if ($existingItem) {
            return $existingItem;
        }

        return $list->items()->create([
            'title_id' => $title->id,
            'position' => (int) $list->items()->max('position') + 1,
        ]);
    }
}
