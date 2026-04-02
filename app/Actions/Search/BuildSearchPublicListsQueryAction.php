<?php

namespace App\Actions\Search;

use App\Enums\ListVisibility;
use App\Enums\MediaKind;
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
                'user:id,name,username,avatar_path',
                'items' => fn ($itemQuery) => $itemQuery
                    ->select([
                        'id',
                        'user_list_id',
                        'title_id',
                        'position',
                    ])
                    ->whereHas('title', fn (Builder $titleQuery) => $titleQuery->publishedCatalog())
                    ->orderBy('position')
                    ->limit(3)
                    ->with([
                        'title:id,name,slug,title_type,release_year,plot_outline',
                        'title.mediaAssets' => fn ($mediaQuery) => $mediaQuery
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
                            ->whereIn('kind', [MediaKind::Poster, MediaKind::Backdrop])
                            ->orderBy('position')
                            ->limit(1),
                    ]),
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

            $searchPrefix = $search.'%';

            $query->orderByRaw(
                'case
                    when lower(name) = lower(?) then 0
                    when lower(slug) = lower(?) then 1
                    when lower(name) like lower(?) then 2
                    when lower(slug) like lower(?) then 3
                    else 4
                end',
                [$search, $search, $searchPrefix, $searchPrefix],
            );
        }

        return $query
            ->orderByDesc('published_items_count')
            ->orderByDesc('updated_at')
            ->orderBy('name');
    }
}
