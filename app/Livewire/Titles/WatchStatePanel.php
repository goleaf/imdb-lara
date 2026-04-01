<?php

namespace App\Livewire\Titles;

use App\Actions\Titles\GetUserWatchStateForTitleAction;
use App\Actions\Titles\SetUserWatchStateForTitleAction;
use App\Enums\WatchState;
use App\Models\Title;
use Carbon\CarbonInterface;
use Livewire\Component;

class WatchStatePanel extends Component
{
    public Title $title;

    public ?WatchState $watchState = null;

    public ?CarbonInterface $startedAt = null;

    public ?CarbonInterface $watchedAt = null;

    public ?string $statusMessage = null;

    public function mount(Title $title, GetUserWatchStateForTitleAction $getUserWatchStateForTitle): void
    {
        $this->title = $title;

        if (! auth()->check()) {
            return;
        }

        $watchStateData = $getUserWatchStateForTitle->handle(auth()->user(), $title);

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
    }

    public function render()
    {
        return view('livewire.titles.watch-state-panel');
    }
}
