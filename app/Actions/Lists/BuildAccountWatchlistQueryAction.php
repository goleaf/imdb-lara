<?php

namespace App\Actions\Lists;

use App\Enums\TitleType;
use App\Enums\WatchState;
use App\Models\Genre;
use App\Models\ListItem;
use App\Models\Title;
use App\Models\UserList;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

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
     * @return EloquentCollection<int, ListItem>
     */
    public function handle(UserList $watchlist, array $filters = []): EloquentCollection
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
            ->where('user_list_id', $watchlist->id);

        match ($state) {
            'watched' => $query->where('watch_state', WatchState::Completed),
            'unwatched' => $query->where('watch_state', '!=', WatchState::Completed),
            default => $this->applySpecificStateFilter($query, $state),
        };

        $titleIds = $this->matchingTitleIds($watchlist, $genre, $type, $year);

        if ($titleIds === []) {
            return new EloquentCollection;
        }

        $items = $query
            ->whereIn('title_id', $titleIds)
            ->with([
                'title' => fn ($titleQuery) => $titleQuery
                    ->select(Title::catalogCardColumns())
                    ->whereIn('id', $titleIds)
                    ->withCatalogCardRelations(),
            ])
            ->get()
            ->filter(fn (ListItem $item): bool => $item->title !== null)
            ->values();

        return new EloquentCollection(
            $this->sortItems(new EloquentCollection($items->all()), $sort)->all(),
        );
    }

    private function applySpecificStateFilter(Builder $query, string $state): void
    {
        $watchState = collect(WatchState::cases())
            ->first(fn (WatchState $candidate): bool => $candidate->value === $state);

        if ($watchState instanceof WatchState) {
            $query->where('watch_state', $watchState);
        }
    }

    /**
     * @return list<int>
     */
    private function matchingTitleIds(UserList $watchlist, ?string $genre, ?string $type, ?int $year): array
    {
        $titleIds = $watchlist->items()
            ->pluck('title_id')
            ->filter()
            ->map(fn (mixed $titleId): int => (int) $titleId)
            ->unique()
            ->values();

        if ($titleIds->isEmpty()) {
            return [];
        }

        $titleQuery = Title::query()
            ->select(Title::catalogCardColumns())
            ->whereIn('id', $titleIds->all());

        if ($type !== null) {
            $titleType = collect(TitleType::cases())
                ->first(fn (TitleType $candidate): bool => $candidate->value === $type);

            if ($titleType instanceof TitleType) {
                $titleQuery->forType($titleType);
            }
        }

        if ($genre !== null) {
            $genreId = $this->genreIdFromSlug($genre);

            if ($genreId !== null) {
                $titleQuery->whereHas('genres', fn (Builder $genreQuery) => $genreQuery->where('genres.id', $genreId));
            }
        }

        if ($year !== null) {
            $titleQuery->where('startyear', $year);
        }

        return $titleQuery
            ->pluck('id')
            ->map(fn (mixed $titleId): int => (int) $titleId)
            ->all();
    }

    private function genreIdFromSlug(string $genre): ?int
    {
        if (preg_match('/-g(?P<id>\d+)$/', $genre, $matches) === 1) {
            return (int) $matches['id'];
        }

        return ctype_digit($genre) ? (int) $genre : null;
    }

    /**
     * @return EloquentCollection<int, ListItem>
     */
    private function sortItems(EloquentCollection $items, string $sort): EloquentCollection
    {
        $sortedItems = match ($sort) {
            'rating' => $items->sort(fn (ListItem $left, ListItem $right): int => $this->compareByRating($left, $right)),
            'title' => $items->sort(fn (ListItem $left, ListItem $right): int => $this->compareByTitle($left, $right)),
            'year' => $items->sort(fn (ListItem $left, ListItem $right): int => $this->compareByYear($left, $right)),
            default => $items->sort(fn (ListItem $left, ListItem $right): int => $this->compareByAddedAt($left, $right)),
        };

        return new EloquentCollection($sortedItems->values()->all());
    }

    private function compareByRating(ListItem $left, ListItem $right): int
    {
        return $this->compareTuples(
            [
                -(int) round(($left->title?->displayAverageRating() ?? 0) * 100),
                -($left->title?->displayRatingCount() ?? 0),
                mb_strtolower($left->title?->name ?? ''),
                -($left->created_at?->getTimestamp() ?? 0),
                -$left->id,
            ],
            [
                -(int) round(($right->title?->displayAverageRating() ?? 0) * 100),
                -($right->title?->displayRatingCount() ?? 0),
                mb_strtolower($right->title?->name ?? ''),
                -($right->created_at?->getTimestamp() ?? 0),
                -$right->id,
            ],
        );
    }

    private function compareByTitle(ListItem $left, ListItem $right): int
    {
        return $this->compareTuples(
            [
                mb_strtolower($left->title?->name ?? ''),
                -($left->title?->release_year ?? 0),
                -($left->created_at?->getTimestamp() ?? 0),
                -$left->id,
            ],
            [
                mb_strtolower($right->title?->name ?? ''),
                -($right->title?->release_year ?? 0),
                -($right->created_at?->getTimestamp() ?? 0),
                -$right->id,
            ],
        );
    }

    private function compareByYear(ListItem $left, ListItem $right): int
    {
        return $this->compareTuples(
            [
                -($left->title?->release_year ?? 0),
                mb_strtolower($left->title?->name ?? ''),
                -($left->created_at?->getTimestamp() ?? 0),
                -$left->id,
            ],
            [
                -($right->title?->release_year ?? 0),
                mb_strtolower($right->title?->name ?? ''),
                -($right->created_at?->getTimestamp() ?? 0),
                -$right->id,
            ],
        );
    }

    private function compareByAddedAt(ListItem $left, ListItem $right): int
    {
        return $this->compareTuples(
            [
                -($left->created_at?->getTimestamp() ?? 0),
                -$left->id,
            ],
            [
                -($right->created_at?->getTimestamp() ?? 0),
                -$right->id,
            ],
        );
    }

    /**
     * @param  list<int|string>  $left
     * @param  list<int|string>  $right
     */
    private function compareTuples(array $left, array $right): int
    {
        foreach ($left as $index => $leftValue) {
            $rightValue = $right[$index];

            if ($leftValue === $rightValue) {
                continue;
            }

            return $leftValue <=> $rightValue;
        }

        return 0;
    }
}
