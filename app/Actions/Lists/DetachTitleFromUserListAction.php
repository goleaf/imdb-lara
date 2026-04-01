<?php

namespace App\Actions\Lists;

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
            ->each(function ($item, int $index): void {
                $item->forceFill([
                    'position' => $index + 1,
                ])->save();
            });
    }
}
