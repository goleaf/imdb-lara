<?php

namespace App\Actions\Lists;

use App\Models\ListItem;
use App\Models\Title;
use App\Models\UserList;
use Illuminate\Database\Eloquent\Builder;

class BuildUserListItemsQueryAction
{
    public function handle(UserList $list): Builder
    {
        return ListItem::query()
            ->select([
                'id',
                'user_list_id',
                'title_id',
                'notes',
                'position',
                'created_at',
                'updated_at',
            ])
            ->where('user_list_id', $list->id)
            ->whereHas('title', fn (Builder $titleQuery) => $titleQuery->publishedCatalog())
            ->with([
                'title' => fn ($titleQuery) => $titleQuery
                    ->select(Title::catalogCardColumns())
                    ->publishedCatalog()
                    ->withCatalogCardRelations(),
            ])
            ->orderBy('position');
    }
}
