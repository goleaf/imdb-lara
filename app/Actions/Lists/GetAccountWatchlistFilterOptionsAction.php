<?php

namespace App\Actions\Lists;

use App\Enums\TitleType;
use App\Enums\WatchState;
use App\Models\Genre;
use App\Models\Title;
use App\Models\UserList;

class GetAccountWatchlistFilterOptionsAction
{
    /**
     * @return array{
     *     genres: \Illuminate\Support\Collection<int, Genre>,
     *     sortOptions: list<array{label: string, value: string}>,
     *     stateOptions: list<array{label: string, value: string}>,
     *     titleTypes: list<TitleType>,
     *     years: \Illuminate\Support\Collection<int, int>
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
            'stateOptions' => [
                ['value' => 'all', 'label' => 'All titles'],
                ['value' => 'watched', 'label' => 'Watched'],
                ['value' => 'unwatched', 'label' => 'Unwatched'],
                ...collect(WatchState::cases())
                    ->map(fn (WatchState $watchState): array => [
                        'value' => $watchState->value,
                        'label' => str($watchState->value)->headline()->value(),
                    ])
                    ->all(),
            ],
            'sortOptions' => [
                ['value' => 'added', 'label' => 'Date added'],
                ['value' => 'year', 'label' => 'Release year'],
                ['value' => 'rating', 'label' => 'Rating'],
                ['value' => 'title', 'label' => 'Title'],
            ],
        ];
    }
}
