<?php

namespace App\Actions\Lists;

use App\Models\Title;
use App\Models\User;

class GetSelectedOwnedCustomListIdsAction
{
    public function __construct(
        public BuildOwnedCustomListsQueryAction $buildOwnedCustomListsQuery,
    ) {}

    /**
     * @return array<int, bool>
     */
    public function handle(User $user, Title $title): array
    {
        return $this->buildOwnedCustomListsQuery
            ->handle($user)
            ->whereHas('items', fn ($query) => $query->where('title_id', $title->id))
            ->pluck('id')
            ->mapWithKeys(fn (int $listId): array => [$listId => true])
            ->all();
    }
}
