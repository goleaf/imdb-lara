<?php

namespace App\Livewire\Account;

use App\Actions\Lists\BuildAccountWatchlistQueryAction;
use App\Actions\Lists\EnsureWatchlistAction;
use App\Actions\Lists\GetAccountWatchlistFilterOptionsAction;
use App\Actions\Lists\ToggleWatchlistItemAction;
use App\Actions\Lists\UpdateWatchlistVisibilityAction;
use App\Actions\Titles\GetUserWatchStateForTitleAction;
use App\Actions\Titles\SetUserWatchStateForTitleAction;
use App\Enums\ListVisibility;
use App\Enums\WatchState;
use App\Models\ListItem;
use App\Models\Title;
use App\Models\UserList;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class WatchlistBrowser extends Component
{
    use WithPagination;

    protected BuildAccountWatchlistQueryAction $buildAccountWatchlistQuery;

    protected EnsureWatchlistAction $ensureWatchlist;

    protected GetAccountWatchlistFilterOptionsAction $getAccountWatchlistFilterOptions;

    protected GetUserWatchStateForTitleAction $getUserWatchStateForTitle;

    protected SetUserWatchStateForTitleAction $setUserWatchStateForTitle;

    protected ToggleWatchlistItemAction $toggleWatchlistItem;

    protected UpdateWatchlistVisibilityAction $updateWatchlistVisibility;

    #[Url]
    public ?string $genre = null;

    #[Url]
    public string $sort = 'added';

    #[Url]
    public string $state = 'all';

    #[Url]
    public ?string $type = null;

    #[Url]
    public ?string $year = null;

    public string $visibility = ListVisibility::Private->value;

    public ?string $statusMessage = null;

    public ?string $visibilityMessage = null;

    public function clearFilters(): void
    {
        $this->genre = null;
        $this->sort = 'added';
        $this->state = 'all';
        $this->type = null;
        $this->year = null;
        $this->resetWatchlistPage();
    }

    public function boot(
        BuildAccountWatchlistQueryAction $buildAccountWatchlistQuery,
        EnsureWatchlistAction $ensureWatchlist,
        GetAccountWatchlistFilterOptionsAction $getAccountWatchlistFilterOptions,
        GetUserWatchStateForTitleAction $getUserWatchStateForTitle,
        SetUserWatchStateForTitleAction $setUserWatchStateForTitle,
        ToggleWatchlistItemAction $toggleWatchlistItem,
        UpdateWatchlistVisibilityAction $updateWatchlistVisibility,
    ): void {
        $this->buildAccountWatchlistQuery = $buildAccountWatchlistQuery;
        $this->ensureWatchlist = $ensureWatchlist;
        $this->getAccountWatchlistFilterOptions = $getAccountWatchlistFilterOptions;
        $this->getUserWatchStateForTitle = $getUserWatchStateForTitle;
        $this->setUserWatchStateForTitle = $setUserWatchStateForTitle;
        $this->toggleWatchlistItem = $toggleWatchlistItem;
        $this->updateWatchlistVisibility = $updateWatchlistVisibility;
    }

    public function mount(): void
    {
        if (! auth()->check()) {
            return;
        }

        $watchlist = $this->ensureWatchlist->handle(auth()->user());
        $this->visibility = $watchlist->visibility->value;
    }

    public function updatedGenre(): void
    {
        $this->resetWatchlistPage();
    }

    public function updatedSort(): void
    {
        $this->resetWatchlistPage();
    }

    public function updatedState(): void
    {
        $this->resetWatchlistPage();
    }

    public function updatedType(): void
    {
        $this->resetWatchlistPage();
    }

    public function updatedYear(): void
    {
        $this->resetWatchlistPage();
    }

    public function toggleWatched(int $titleId): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $user = auth()->user();
        $title = Title::query()->select(['id', 'slug'])->findOrFail($titleId);
        $watchStateData = $this->getUserWatchStateForTitle->handle($user, $title);
        $currentState = $watchStateData['state'] ?? null;
        $targetState = $currentState === WatchState::Completed
            ? WatchState::Planned
            : WatchState::Completed;

        $this->setUserWatchStateForTitle->handle($user, $title, $targetState);
        $this->statusMessage = $targetState === WatchState::Completed
            ? 'Marked as watched.'
            : 'Marked as unwatched.';
    }

    public function removeFromWatchlist(int $titleId): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $user = auth()->user();
        $entryExists = $user->watchlistEntries()
            ->where('title_id', $titleId)
            ->exists();

        if (! $entryExists) {
            return;
        }

        $title = Title::query()->select(['id', 'slug'])->findOrFail($titleId);
        $this->toggleWatchlistItem->handle($user, $title);
        $this->statusMessage = 'Removed from watchlist.';
    }

    public function saveVisibility(): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $this->validate([
            'visibility' => [
                'required',
                Rule::in([
                    ListVisibility::Private->value,
                    ListVisibility::Public->value,
                ]),
            ],
        ]);

        $watchlist = $this->ensureWatchlist->handle(auth()->user());
        $watchlist = $this->updateWatchlistVisibility->handle(
            $watchlist,
            ListVisibility::from($this->visibility),
        );

        $this->visibility = $watchlist->visibility->value;
        $this->visibilityMessage = 'Watchlist visibility updated.';
    }

    public function render(): View
    {
        $user = auth()->user();
        $watchlist = $this->ensureWatchlist->handle($user);
        $pageName = 'watchlist';
        $perPage = 12;

        $watchlist->loadCount([
            'items',
            'items as watched_items_count' => fn ($query) => $query->where('watch_state', WatchState::Completed),
        ]);

        $filterOptions = $this->getAccountWatchlistFilterOptions->handle($watchlist);
        $watchlistItems = $this->buildAccountWatchlistQuery
            ->handle($watchlist, [
                'genre' => $this->genre,
                'sort' => $this->sort,
                'state' => $this->state,
                'type' => $this->type,
                'year' => $this->year,
            ]);
        $currentPage = $this->getPage(pageName: $pageName);
        $items = new LengthAwarePaginator(
            items: $watchlistItems->forPage($currentPage, $perPage)->values(),
            total: $watchlistItems->count(),
            perPage: $perPage,
            currentPage: $currentPage,
            options: [
                'pageName' => $pageName,
                'path' => route('account.watchlist'),
            ],
        );
        $items->withQueryString();

        return view('livewire.account.watchlist-browser', [
            'emptyState' => $this->emptyState($watchlist),
            'filterOptions' => $filterOptions,
            'hasActiveFilters' => $this->hasActiveFilters(),
            'items' => $items,
            'itemActionStates' => $this->itemActionStates($items),
            'publicWatchlistUrl' => $this->publicWatchlistUrl($watchlist),
            'summaryBadges' => $this->summaryBadges($watchlist),
            'visibilityOptions' => $this->visibilityOptions(),
        ]);
    }

    public function placeholder(): View
    {
        return view('livewire.placeholders.account-watchlist-browser');
    }

    private function resetWatchlistPage(): void
    {
        $this->resetPage(pageName: 'watchlist');
    }

    /**
     * @return array{heading: string, text: string}
     */
    private function emptyState(UserList $watchlist): array
    {
        return (int) $watchlist->items_count === 0
            ? [
                'heading' => 'Your watchlist is empty.',
                'text' => 'Save titles from any title page to build a private queue you can sort and filter here.',
            ]
            : [
                'heading' => 'No titles match the current watchlist filters.',
                'text' => 'Adjust the state, genre, year, or type filters to widen the page.',
            ];
    }

    private function hasActiveFilters(): bool
    {
        return (filled($this->state) && $this->state !== 'all')
            || filled($this->type)
            || filled($this->genre)
            || filled($this->year)
            || $this->sort !== 'added';
    }

    /**
     * @return array<int, array{
     *     removeTarget: string,
     *     toggleWatchLabel: string,
     *     toggleWatchTarget: string,
     *     toggleWatchVariant: string
     * }>
     */
    private function itemActionStates(LengthAwarePaginator $items): array
    {
        return collect($items->items())
            ->filter(fn (mixed $item): bool => $item instanceof ListItem)
            ->mapWithKeys(fn (ListItem $item): array => [$item->id => $this->itemActionState($item)])
            ->all();
    }

    /**
     * @return array{
     *     removeTarget: string,
     *     toggleWatchLabel: string,
     *     toggleWatchTarget: string,
     *     toggleWatchVariant: string
     * }
     */
    private function itemActionState(ListItem $item): array
    {
        $toggleWatchTarget = "toggleWatched({$item->title_id})";

        return [
            'removeTarget' => "removeFromWatchlist({$item->title_id})",
            'toggleWatchLabel' => $item->watch_state === WatchState::Completed ? 'Mark unwatched' : 'Mark watched',
            'toggleWatchTarget' => $toggleWatchTarget,
            'toggleWatchVariant' => $item->watch_state === WatchState::Completed ? 'outline' : 'primary',
        ];
    }

    private function publicWatchlistUrl(UserList $watchlist): ?string
    {
        if ($watchlist->visibility !== ListVisibility::Public || ! auth()->check()) {
            return null;
        }

        return route('public.lists.show', [auth()->user(), $watchlist]);
    }

    /**
     * @return list<array{color: string, icon: string, label: string}>
     */
    private function summaryBadges(UserList $watchlist): array
    {
        $savedCount = (int) $watchlist->items_count;
        $watchedCount = (int) $watchlist->watched_items_count;
        $queuedCount = max(0, $savedCount - $watchedCount);

        return [
            [
                'color' => 'neutral',
                'icon' => 'bookmark',
                'label' => number_format($savedCount).' saved',
            ],
            [
                'color' => 'green',
                'icon' => 'check-circle',
                'label' => number_format($watchedCount).' watched',
            ],
            [
                'color' => 'slate',
                'icon' => 'queue-list',
                'label' => number_format($queuedCount).' queued',
            ],
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function visibilityOptions(): array
    {
        return [
            ['value' => ListVisibility::Private->value, 'label' => ListVisibility::Private->label()],
            ['value' => ListVisibility::Public->value, 'label' => ListVisibility::Public->label()],
        ];
    }
}
