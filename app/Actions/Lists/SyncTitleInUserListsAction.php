<?php

namespace App\Actions\Lists;

use App\Models\Title;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Database\Eloquent\Collection;

class SyncTitleInUserListsAction
{
    public function __construct(
        protected AttachTitleToUserListAction $attachTitleToUserList,
        protected DetachTitleFromUserListAction $detachTitleFromUserList,
    ) {}

    /**
     * @param  list<int>  $selectedListIds
     */
    public function handle(User $user, Title $title, array $selectedListIds): void
    {
        /** @var Collection<int, UserList> $lists */
        $lists = UserList::query()
            ->select(['id', 'user_id', 'name', 'slug', 'visibility', 'is_watchlist'])
            ->whereBelongsTo($user)
            ->where('is_watchlist', false)
            ->get();

        $ownedListIds = $lists->modelKeys();
        $allowedSelectedListIds = collect($selectedListIds)
            ->intersect($ownedListIds)
            ->map(fn ($listId): int => (int) $listId)
            ->values()
            ->all();

        $lists->each(function (UserList $list) use ($allowedSelectedListIds, $title): void {
            if (! in_array($list->id, $allowedSelectedListIds, true)) {
                $this->detachTitleFromUserList->handle($list, $title->id);
            }
        });

        foreach ($allowedSelectedListIds as $listId) {
            $list = $lists->firstWhere('id', $listId);

            if (! $list) {
                continue;
            }

            $this->attachTitleToUserList->handle($list, $title);
        }
    }
}
