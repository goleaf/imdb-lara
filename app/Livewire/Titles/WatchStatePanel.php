<?php

namespace App\Livewire\Titles;

use App\Actions\Titles\GetUserWatchStateForTitleAction;
use App\Actions\Titles\SetUserWatchStateForTitleAction;
use App\Enums\WatchState;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class WatchStatePanel extends Component
{
    use AuthorizesRequests;

    protected GetUserWatchStateForTitleAction $getUserWatchStateForTitle;

    #[Locked]
    public Title $title;

    public ?WatchState $watchState = null;

    public bool $isCompleted = false;

    public ?Carbon $startedAt = null;

    public ?Carbon $watchedAt = null;

    public ?string $statusMessage = null;

    public function boot(GetUserWatchStateForTitleAction $getUserWatchStateForTitle): void
    {
        $this->getUserWatchStateForTitle = $getUserWatchStateForTitle;
    }

    public function mount(Title $title): void
    {
        $this->title = $title;
        $this->refreshTrackingState();
    }

    #[On('title-personal-tracking-updated')]
    public function refreshTrackingState(): void
    {
        if (! auth()->check()) {
            $this->applyTrackingState();

            return;
        }

        $watchStateData = $this->getUserWatchStateForTitle->handle(auth()->user(), $this->title);

        $this->applyTrackingState(
            $watchStateData['state'] ?? null,
            $watchStateData['started_at'] ?? null,
            $watchStateData['watched_at'] ?? null,
        );
    }

    public function markWatched(SetUserWatchStateForTitleAction $setUserWatchState): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $this->authorize('track', $this->title);

        $watchlistEntry = $setUserWatchState->handle(auth()->user(), $this->title, WatchState::Completed);

        $this->applyTrackingState(
            $watchlistEntry->watch_state,
            $watchlistEntry->started_at,
            $watchlistEntry->watched_at,
        );
        $this->statusMessage = 'Marked as watched.';
        $this->title->refresh()->load('statistic');
        $this->dispatch('title-personal-tracking-updated');
    }

    public function toggleWatched(SetUserWatchStateForTitleAction $setUserWatchState): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $this->authorize('track', $this->title);

        $targetState = $this->watchState === WatchState::Completed
            ? WatchState::Planned
            : WatchState::Completed;

        $watchlistEntry = $setUserWatchState->handle(auth()->user(), $this->title, $targetState);

        $this->applyTrackingState(
            $watchlistEntry->watch_state,
            $watchlistEntry->started_at,
            $watchlistEntry->watched_at,
        );
        $this->statusMessage = $targetState === WatchState::Completed
            ? 'Marked as watched.'
            : 'Marked as unwatched.';
        $this->title->refresh()->load('statistic');
        $this->dispatch('title-personal-tracking-updated');
    }

    #[Computed]
    public function viewData(): array
    {
        return [
            'buttonVariant' => $this->isCompleted ? 'outline' : 'primary',
            'trackedStateColor' => $this->isCompleted ? 'green' : 'neutral',
            'trackedStateLabel' => $this->watchState
                ? (string) str($this->watchState->value)->headline()
                : 'Not tracked yet',
        ];
    }

    private function applyTrackingState(
        ?WatchState $watchState = null,
        ?Carbon $startedAt = null,
        ?Carbon $watchedAt = null,
    ): void {
        $this->watchState = $watchState;
        $this->startedAt = $startedAt;
        $this->watchedAt = $watchedAt;
        $this->isCompleted = $watchState === WatchState::Completed;
    }

    public function render(): View
    {
        return view('livewire.titles.watch-state-panel', $this->viewData);
    }
}
