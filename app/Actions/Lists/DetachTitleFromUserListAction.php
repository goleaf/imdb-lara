<?php

namespace App\Actions\Lists;

use App\Models\ListItem;
use App\Models\UserList;

class DetachTitleFromUserListAction
{
    public function handle(UserList $list, int $titleId): void
    {
        $deleted = $list->items()
            ->where('title_id', $titleId)
            ->delete();

        if ($deleted === 0) {
            return;
        }

        $this->normalizePositions($list);
    }

    private function normalizePositions(UserList $list): void
    {
        $list->items()
            ->select(['id'])
            ->orderBy('position')
            ->get()
            ->values()
            ->each(function (ListItem $item, int $index): void {
                ListItem::query()
                    ->whereKey($item->id)
                    ->update([
                        'position' => $index + 1,
                    ]);
            });
    }
}
