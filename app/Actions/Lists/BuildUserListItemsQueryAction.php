<?php

namespace App\Actions\Lists;

use App\Enums\MediaKind;
use App\Models\ListItem;
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
            ->with([
                'title' => fn ($titleQuery) => $titleQuery
                    ->select([
                        'id',
                        'name',
                        'slug',
                        'title_type',
                        'release_year',
                        'plot_outline',
                    ])
                    ->with([
                        'genres:id,name,slug',
                        'statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count',
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
            ])
            ->orderBy('position');
    }
}
