<?php

namespace App\Actions\Lists;

use App\Enums\ListVisibility;
use App\Models\Title;
use App\Models\UserList;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class BuildPublicListsIndexQueryAction
{
    public function handle(?string $search = null, string $sort = 'recent'): Builder
    {
        $normalizedSearch = trim((string) $search);

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
                'created_at',
            ])
            ->custom()
            ->where('visibility', ListVisibility::Public);

        if (! $this->catalogTitlesAreQueryable()) {
            return $query->whereKey([]);
        }

        $query
            ->whereHas('items', fn (Builder $itemQuery) => $itemQuery
                ->whereHas('title', fn (Builder $titleQuery) => $titleQuery->publishedCatalog()))
            ->withCount([
                'items as published_items_count' => fn (Builder $itemQuery) => $itemQuery
                    ->whereHas('title', fn (Builder $titleQuery) => $titleQuery->publishedCatalog()),
            ])
            ->with([
                'user:id,name,username',
                'items' => fn ($itemQuery) => $itemQuery
                    ->select(['id', 'user_list_id', 'title_id', 'position'])
                    ->whereHas('title', fn (Builder $titleQuery) => $titleQuery->publishedCatalog())
                    ->orderBy('position')
                    ->with([
                        'title' => fn ($titleQuery) => $titleQuery
                            ->select(Title::catalogCardColumns())
                            ->publishedCatalog()
                            ->withCatalogCardRelations(),
                    ])
                    ->limit(3),
            ]);

        if ($normalizedSearch !== '') {
            $likeSearch = "%{$normalizedSearch}%";

            $query->where(function (Builder $searchQuery) use ($likeSearch): void {
                $searchQuery
                    ->where('name', 'like', $likeSearch)
                    ->orWhere('slug', 'like', $likeSearch)
                    ->orWhere('description', 'like', $likeSearch)
                    ->orWhereHas('user', function (Builder $userQuery) use ($likeSearch): void {
                        $userQuery
                            ->where('name', 'like', $likeSearch)
                            ->orWhere('username', 'like', $likeSearch);
                    });
            });
        }

        return match ($sort) {
            'most_titles' => $query
                ->orderByDesc('published_items_count')
                ->orderByDesc('updated_at')
                ->orderBy('name'),
            'title' => $query
                ->orderBy('name')
                ->orderByDesc('updated_at'),
            default => $query
                ->orderByDesc('updated_at')
                ->orderByDesc('id'),
        };
    }

    private function catalogTitlesAreQueryable(): bool
    {
        if (! Title::usesCatalogOnlySchema()) {
            return true;
        }

        $listConnection = (new UserList)->getConnectionName();
        $title = new Title;

        return $listConnection === $title->getConnectionName()
            && Schema::connection($listConnection)->hasTable($title->getTable());
    }
}
