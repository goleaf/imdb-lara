<?php

namespace App\Actions\Lists;

use App\Enums\MediaKind;
use App\Enums\WatchState;
use App\Models\ListItem;
use App\Models\Title;
use App\Models\TitleStatistic;
use App\Models\UserList;
use Illuminate\Database\Eloquent\Builder;

class BuildAccountWatchlistQueryAction
{
    /**
     * @param  array{
     *     genre?: string|null,
     *     sort?: string|null,
     *     state?: string|null,
     *     type?: string|null,
     *     year?: int|string|null
     * }  $filters
     */
    public function handle(UserList $watchlist, array $filters = []): Builder
    {
        $genre = filled($filters['genre'] ?? null) ? (string) $filters['genre'] : null;
        $sort = filled($filters['sort'] ?? null) ? (string) $filters['sort'] : 'added';
        $state = filled($filters['state'] ?? null) ? (string) $filters['state'] : 'all';
        $type = filled($filters['type'] ?? null) ? (string) $filters['type'] : null;
        $year = filled($filters['year'] ?? null) ? (int) $filters['year'] : null;

        $query = ListItem::query()
            ->select([
                'id',
                'user_list_id',
                'title_id',
                'position',
                'watch_state',
                'started_at',
                'watched_at',
                'created_at',
            ])
            ->where('user_list_id', $watchlist->id)
            ->with([
                'title' => fn ($titleQuery) => $titleQuery
                    ->select([
                        'id',
                        'name',
                        'slug',
                        'sort_title',
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
            ]);

        if ($type !== null) {
            $query->whereHas('title', fn (Builder $titleQuery) => $titleQuery->where('title_type', $type));
        }

        if ($genre !== null) {
            $query->whereHas(
                'title.genres',
                fn (Builder $genreQuery) => $genreQuery->where('slug', $genre),
            );
        }

        if ($year !== null) {
            $query->whereHas('title', fn (Builder $titleQuery) => $titleQuery->where('release_year', $year));
        }

        match ($state) {
            'watched' => $query->where('watch_state', WatchState::Completed),
            'unwatched' => $query->where('watch_state', '!=', WatchState::Completed),
            default => $this->applySpecificStateFilter($query, $state),
        };

        return match ($sort) {
            'rating' => $query
                ->orderByDesc(self::statisticColumnSubquery('average_rating'))
                ->orderByDesc(self::statisticColumnSubquery('rating_count'))
                ->orderBy(self::titleColumnSubquery('name'))
                ->orderByDesc('created_at'),
            'title' => $query
                ->orderBy(self::titleColumnSubquery('name'))
                ->orderByDesc(self::titleColumnSubquery('release_year'))
                ->orderByDesc('created_at'),
            'year' => $query
                ->orderByDesc(self::titleColumnSubquery('release_year'))
                ->orderBy(self::titleColumnSubquery('name'))
                ->orderByDesc('created_at'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };
    }

    private function applySpecificStateFilter(Builder $query, string $state): void
    {
        $watchState = collect(WatchState::cases())
            ->first(fn (WatchState $candidate): bool => $candidate->value === $state);

        if ($watchState instanceof WatchState) {
            $query->where('watch_state', $watchState);
        }
    }

    private static function titleColumnSubquery(string $column): Builder
    {
        return Title::query()
            ->select($column)
            ->whereColumn('titles.id', 'list_items.title_id')
            ->limit(1);
    }

    private static function statisticColumnSubquery(string $column): Builder
    {
        return TitleStatistic::query()
            ->select($column)
            ->whereColumn('title_statistics.title_id', 'list_items.title_id')
            ->limit(1);
    }
}
