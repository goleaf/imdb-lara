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
     * @return list<string>
     */
    public function handle(User $user, Title $title): array
    {
        return $this->buildOwnedCustomListsQuery
            ->handle($user)
            ->whereHas('items', fn ($query) => $query->where('title_id', $title->id))
            ->pluck('id')
            ->map(fn (int $listId): string => (string) $listId)
            ->values()
            ->all();
    }
}
