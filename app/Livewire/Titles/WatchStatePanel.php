<?php

namespace App\Livewire\Titles;

use App\Actions\Titles\GetUserWatchStateForTitleAction;
use App\Actions\Titles\SetUserWatchStateForTitleAction;
use App\Enums\WatchState;
use App\Models\Title;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class WatchStatePanel extends Component
{
    protected GetUserWatchStateForTitleAction $getUserWatchStateForTitle;

    public Title $title;

    public ?WatchState $watchState = null;

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
            $this->watchState = null;
            $this->startedAt = null;
            $this->watchedAt = null;

            return;
        }

        $watchStateData = $this->getUserWatchStateForTitle->handle(auth()->user(), $this->title);

        $this->watchState = $watchStateData['state'] ?? null;
        $this->startedAt = $watchStateData['started_at'] ?? null;
        $this->watchedAt = $watchStateData['watched_at'] ?? null;
    }

    public function markWatched(SetUserWatchStateForTitleAction $setUserWatchState): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $watchlistEntry = $setUserWatchState->handle(auth()->user(), $this->title, WatchState::Completed);

        $this->watchState = $watchlistEntry->watch_state;
        $this->startedAt = $watchlistEntry->started_at;
        $this->watchedAt = $watchlistEntry->watched_at;
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

        $targetState = $this->watchState === WatchState::Completed
            ? WatchState::Planned
            : WatchState::Completed;

        $watchlistEntry = $setUserWatchState->handle(auth()->user(), $this->title, $targetState);

        $this->watchState = $watchlistEntry->watch_state;
        $this->startedAt = $watchlistEntry->started_at;
        $this->watchedAt = $watchlistEntry->watched_at;
        $this->statusMessage = $targetState === WatchState::Completed
            ? 'Marked as watched.'
            : 'Marked as unwatched.';
        $this->title->refresh()->load('statistic');
        $this->dispatch('title-personal-tracking-updated');
    }

    public function render()
    {
        return view('livewire.titles.watch-state-panel');
    }
}
