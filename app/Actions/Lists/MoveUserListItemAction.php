<?php

namespace App\Actions\Lists;

use App\Models\ListItem;
use App\Models\UserList;
use Illuminate\Support\Facades\DB;

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

    public function reorder(UserList $list, int $itemId, int $targetPosition): void
    {
        $list->items()
            ->select(['id'])
            ->findOrFail($itemId);

        $orderedItemIds = $list->items()
            ->select(['id'])
            ->orderBy('position')
            ->pluck('id')
            ->all();

        $currentIndex = array_search($itemId, $orderedItemIds, true);
        if ($currentIndex === false) {
            return;
        }

        $targetIndex = min(
            max($targetPosition - 1, 0),
            max(count($orderedItemIds) - 1, 0),
        );

        if ($currentIndex === $targetIndex) {
            return;
        }

        array_splice($orderedItemIds, $currentIndex, 1);
        array_splice($orderedItemIds, $targetIndex, 0, [$itemId]);

        DB::transaction(function () use ($orderedItemIds): void {
            collect($orderedItemIds)
                ->values()
                ->each(function (int $orderedItemId, int $index): void {
                    ListItem::query()
                        ->whereKey($orderedItemId)
                        ->update([
                            'position' => $index + 1,
                        ]);
                });
        });
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
