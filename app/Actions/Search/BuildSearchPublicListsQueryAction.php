<?php

namespace App\Actions\Search;

use App\Enums\ListVisibility;
use App\Models\UserList;
use Illuminate\Database\Eloquent\Builder;

class BuildSearchPublicListsQueryAction
{
    public function handle(?string $search = null): Builder
    {
        $search = trim((string) $search);

        $query = UserList::query()
            ->select([
                'id',
                'user_id',
                'name',
                'slug',
                'description',
                'visibility',
                'is_watchlist',
                'updated_at',
            ])
            ->custom()
            ->where('visibility', ListVisibility::Public)
            ->whereHas('items.title', fn (Builder $titleQuery) => $titleQuery->publishedCatalog())
            ->withCount([
                'items as published_items_count' => fn (Builder $itemQuery) => $itemQuery
                    ->whereHas('title', fn (Builder $titleQuery) => $titleQuery->publishedCatalog()),
            ])
            ->with([
                'user:id,name,username',
            ]);

        if ($search !== '') {
            $query->where(function (Builder $listQuery) use ($search): void {
                $listQuery
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                        $userQuery
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('username', 'like', '%'.$search.'%');
                    });
            });
        }

        return $query
            ->orderByDesc('published_items_count')
            ->orderByDesc('updated_at')
            ->orderBy('name');
    }
}
