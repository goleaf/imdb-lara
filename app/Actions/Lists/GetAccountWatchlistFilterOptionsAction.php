<?php

namespace App\Actions\Lists;

use App\Enums\TitleType;
use App\Enums\WatchState;
use App\Models\Genre;
use App\Models\Title;
use App\Models\UserList;
use Illuminate\Support\Collection;

class GetAccountWatchlistFilterOptionsAction
{
    /**
     * @return array{
     *     genres: Collection<int, Genre>,
     *     sortOptions: list<array{icon: string, label: string, value: string}>,
     *     stateOptions: list<array{icon: string, label: string, value: string}>,
     *     titleTypes: list<TitleType>,
     *     years: Collection<int, int>
     * }
     */
    public function handle(UserList $watchlist): array
    {
        $titleBaseQuery = Title::query()
            ->whereHas('listItems', fn ($query) => $query->where('user_list_id', $watchlist->id));

        /** @var list<TitleType> $titleTypes */
        $titleTypes = (clone $titleBaseQuery)
            ->select(['id', 'title_type'])
            ->get()
            ->pluck('title_type')
            ->filter(fn (mixed $titleType): bool => $titleType instanceof TitleType)
            ->unique(fn (TitleType $titleType): string => $titleType->value)
            ->sortBy(fn (TitleType $titleType): string => $titleType->value)
            ->values()
            ->all();

        return [
            'genres' => Genre::query()
                ->select(['genres.id', 'genres.name', 'genres.slug'])
                ->whereHas(
                    'titles.listItems',
                    fn ($query) => $query->where('user_list_id', $watchlist->id),
                )
                ->orderBy('name')
                ->get(),
            'years' => (clone $titleBaseQuery)
                ->whereNotNull('release_year')
                ->orderByDesc('release_year')
                ->pluck('release_year')
                ->unique()
                ->values(),
            'titleTypes' => $titleTypes,
            'stateOptions' => $this->stateOptions(),
            'sortOptions' => $this->sortOptions(),
        ];
    }

    /**
     * @return list<array{icon: string, label: string, value: string}>
     */
    public function stateOptions(): array
    {
        return [
            ['value' => 'all', 'label' => 'All titles', 'icon' => 'squares-2x2'],
            ['value' => 'watched', 'label' => 'Watched', 'icon' => 'check-circle'],
            ['value' => 'unwatched', 'label' => 'Unwatched', 'icon' => 'eye'],
            ...collect(WatchState::cases())
                ->map(fn (WatchState $watchState): array => [
                    'value' => $watchState->value,
                    'label' => str($watchState->value)->headline()->value(),
                    'icon' => $watchState->icon(),
                ])
                ->all(),
        ];
    }

    /**
     * @return list<array{icon: string, label: string, value: string}>
     */
    public function sortOptions(): array
    {
        return [
            ['value' => 'added', 'label' => 'Date added', 'icon' => 'calendar-days'],
            ['value' => 'year', 'label' => 'Release year', 'icon' => 'calendar-days'],
            ['value' => 'rating', 'label' => 'Rating', 'icon' => 'star'],
            ['value' => 'title', 'label' => 'Title', 'icon' => 'bars-arrow-down'],
        ];
    }
}
