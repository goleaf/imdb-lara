<?php

namespace App\Actions\Lists;

use App\Models\ListItem;
use App\Models\UserList;

class MoveUserListItemAction
{
    public function up(UserList $list, int $itemId): void
    {
        $item = $list->items()
            ->select(['id', 'user_list_id', 'position'])
            ->findOrFail($itemId);

        $previousItem = $list->items()
            ->select(['id', 'user_list_id', 'position'])
            ->where('position', '<', $item->position)
            ->orderByDesc('position')
            ->first();

        if (! $previousItem) {
            return;
        }

        $this->swapPositions($item, $previousItem);
    }

    public function down(UserList $list, int $itemId): void
    {
        $item = $list->items()
            ->select(['id', 'user_list_id', 'position'])
            ->findOrFail($itemId);

        $nextItem = $list->items()
            ->select(['id', 'user_list_id', 'position'])
            ->where('position', '>', $item->position)
            ->orderBy('position')
            ->first();

        if (! $nextItem) {
            return;
        }

        $this->swapPositions($item, $nextItem);
    }

    private function swapPositions(ListItem $firstItem, ListItem $secondItem): void
    {
        $firstPosition = $firstItem->position;

        $firstItem->forceFill([
            'position' => $secondItem->position,
        ])->save();

        $secondItem->forceFill([
            'position' => $firstPosition,
        ])->save();
    }
}
