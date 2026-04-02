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
use App\Models\Title;
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

    public function render()
    {
        $user = auth()->user();
        $watchlist = $this->ensureWatchlist->handle($user);

        $watchlist->loadCount([
            'items',
            'items as watched_items_count' => fn ($query) => $query->where('watch_state', WatchState::Completed),
        ]);

        $filterOptions = $this->getAccountWatchlistFilterOptions->handle($watchlist);
        $items = $this->buildAccountWatchlistQuery
            ->handle($watchlist, [
                'genre' => $this->genre,
                'sort' => $this->sort,
                'state' => $this->state,
                'type' => $this->type,
                'year' => $this->year,
            ])
            ->simplePaginate(12, pageName: 'watchlist')
            ->withQueryString();

        return view('livewire.account.watchlist-browser', [
            'filterOptions' => $filterOptions,
            'hasActiveFilters' => (filled($this->state) && $this->state !== 'all')
                || filled($this->type)
                || filled($this->genre)
                || filled($this->year)
                || $this->sort !== 'added',
            'items' => $items,
            'visibilityOptions' => [
                ['value' => ListVisibility::Private->value, 'label' => ListVisibility::Private->label()],
                ['value' => ListVisibility::Public->value, 'label' => ListVisibility::Public->label()],
            ],
            'watchlist' => $watchlist,
        ]);
    }

    private function resetWatchlistPage(): void
    {
        $this->resetPage(pageName: 'watchlist');
    }
}
