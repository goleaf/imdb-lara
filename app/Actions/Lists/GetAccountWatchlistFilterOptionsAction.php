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
        $titleIds = $watchlist->items()
            ->pluck('title_id')
            ->filter()
            ->map(fn (mixed $titleId): int => (int) $titleId)
            ->unique()
            ->values();

        if ($titleIds->isEmpty()) {
            return [
                'genres' => collect(),
                'years' => collect(),
                'titleTypes' => [],
                'stateOptions' => $this->stateOptions(),
                'sortOptions' => $this->sortOptions(),
            ];
        }

        /** @var Collection<int, Title> $titles */
        $titles = Title::query()
            ->select(Title::catalogCardColumns())
            ->publishedCatalog()
            ->whereIn('id', $titleIds->all())
            ->with('genres:id,name')
            ->get();

        /** @var list<TitleType> $titleTypes */
        $titleTypes = $titles
            ->pluck('title_type')
            ->filter(fn (mixed $titleType): bool => $titleType instanceof TitleType)
            ->unique(fn (TitleType $titleType): string => $titleType->value)
            ->sortBy(fn (TitleType $titleType): string => $titleType->value)
            ->values()
            ->all();

        return [
            'genres' => $titles
                ->flatMap(fn (Title $title): Collection => $title->resolvedGenres())
                ->filter(fn (mixed $genre): bool => $genre instanceof Genre)
                ->unique('id')
                ->sortBy('name')
                ->values(),
            'years' => $titles
                ->pluck('release_year')
                ->filter(fn (mixed $releaseYear): bool => is_int($releaseYear))
                ->unique()
                ->sortDesc()
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
