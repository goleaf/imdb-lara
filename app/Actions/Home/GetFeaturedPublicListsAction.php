<?php

namespace App\Actions\Home;

use App\Enums\ListVisibility;
use App\Enums\MediaKind;
use App\Models\UserList;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class GetFeaturedPublicListsAction
{
    public function query(): Builder
    {
        return UserList::query()
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
                            ]),
                    ]),
            ])
            ->orderByDesc('published_items_count')
            ->orderByDesc('updated_at')
            ->orderBy('name');
    }

    /**
     * @return Collection<int, UserList>
     */
    public function handle(int $limit = 6): Collection
    {
        return Cache::remember(
            "home:featured-public-lists:{$limit}",
            now()->addMinutes(10),
            fn (): Collection => $this->query()
                ->limit($limit)
                ->get(),
        );
    }
}
