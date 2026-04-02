<?php

namespace App\Actions\Lists;

use App\Enums\ListVisibility;
use App\Enums\MediaKind;
use App\Models\UserList;
use Illuminate\Database\Eloquent\Builder;

class BuildPublicListsIndexQueryAction
{
    public function handle(?string $search = null, string $sort = 'recent'): Builder
    {
        $normalizedSearch = mb_strtolower(trim((string) $search));

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
            ->where('visibility', ListVisibility::Public)
            ->whereHas('items.title', fn (Builder $titleQuery) => $titleQuery->publishedCatalog())
            ->withCount([
                'items as published_items_count' => fn (Builder $itemQuery) => $itemQuery
                    ->whereHas('title', fn (Builder $titleQuery) => $titleQuery->publishedCatalog()),
            ])
            ->with([
                'user:id,name,username',
                'items' => fn ($itemQuery) => $itemQuery
                    ->select(['id', 'user_list_id', 'title_id', 'position'])
                    ->orderBy('position')
                    ->with([
                        'title' => fn ($titleQuery) => $titleQuery
                            ->select([
                                'id',
                                'name',
                                'slug',
                                'title_type',
                                'release_year',
                                'plot_outline',
                                'is_published',
                            ])
                            ->publishedCatalog()
                            ->with([
                                'mediaAssets' => fn ($mediaQuery) => $mediaQuery
                                    ->select([
                                        'id',
                                        'mediable_type',
                                        'mediable_id',
                                        'kind',
                                        'url',
                                        'alt_text',
                                        'position',
                                        'is_primary',
                                    ])
                                    ->where('kind', MediaKind::Poster)
                                    ->orderBy('position')
                                    ->limit(1),
                            ])
                            ->orderBy('name'),
                    ])
                    ->limit(3),
            ]);

        if ($normalizedSearch !== '') {
            $likeSearch = "%{$normalizedSearch}%";
            $prefixSearch = "{$normalizedSearch}%";

            $query
                ->where(function (Builder $searchQuery) use ($likeSearch): void {
                    $searchQuery
                        ->whereRaw('lower(name) like ?', [$likeSearch])
                        ->orWhereRaw('lower(slug) like ?', [$likeSearch])
                        ->orWhereRaw('lower(description) like ?', [$likeSearch])
                        ->orWhereHas('user', function (Builder $userQuery) use ($likeSearch): void {
                            $userQuery
                                ->whereRaw('lower(name) like ?', [$likeSearch])
                                ->orWhereRaw('lower(username) like ?', [$likeSearch]);
                        });
                })
                ->orderByRaw(
                    'case
                        when lower(name) = ? then 0
                        when lower(slug) = ? then 1
                        when lower(name) like ? then 2
                        when lower(slug) like ? then 3
                        when exists (
                            select 1
                            from users
                            where users.id = user_lists.user_id
                            and (
                                lower(users.name) like ?
                                or lower(users.username) like ?
                            )
                        ) then 4
                        else 5
                    end',
                    [
                        $normalizedSearch,
                        $normalizedSearch,
                        $prefixSearch,
                        $prefixSearch,
                        $prefixSearch,
                        $prefixSearch,
                    ],
                );
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
}
