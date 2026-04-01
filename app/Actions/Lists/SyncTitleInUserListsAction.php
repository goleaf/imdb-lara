<?php

namespace App\Actions\Lists;

use App\Models\Title;
use App\Models\User;
use App\Models\UserList;

class SyncTitleInUserListsAction
{
    /**
     * @param  list<int>  $selectedListIds
     */
    public function handle(User $user, Title $title, array $selectedListIds): void
    {
        $lists = UserList::query()
            ->select(['id'])
            ->whereBelongsTo($user)
            ->where('is_watchlist', false)
            ->get();

        $ownedListIds = $lists->modelKeys();
        $allowedSelectedListIds = collect($selectedListIds)
            ->intersect($ownedListIds)
            ->map(fn ($listId): int => (int) $listId)
            ->values()
            ->all();

        $title->listItems()
            ->whereIn('user_list_id', $ownedListIds)
            ->whereNotIn('user_list_id', $allowedSelectedListIds)
            ->delete();

        foreach ($allowedSelectedListIds as $listId) {
            $list = $lists->firstWhere('id', $listId);

            if (! $list || $title->listItems()->where('user_list_id', $listId)->exists()) {
                continue;
            }

            $list->items()->create([
                'title_id' => $title->id,
                'position' => (int) $list->items()->max('position') + 1,
            ]);
        }
    }
}
