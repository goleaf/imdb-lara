<?php

namespace App\Actions\Lists;

use App\Models\UserList;
use Illuminate\Database\Eloquent\Builder;

class LoadPublicUserListAction
{
    public function handle(UserList $list): UserList
    {
        $list->load([
            'user:id,name,username',
        ])->loadCount([
            'items as items_count' => fn (Builder $itemQuery) => $itemQuery
                ->whereHas('title', fn (Builder $titleQuery) => $titleQuery->publishedCatalog()),
        ]);

        return $list;
    }
}
