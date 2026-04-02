<?php

namespace App\Livewire\Titles;

use App\Actions\Lists\IsTitleInWatchlistAction;
use App\Actions\Lists\ToggleWatchlistItemAction;
use App\Models\Title;
use Livewire\Attributes\On;
use Livewire\Component;

class WatchlistToggle extends Component
{
    protected IsTitleInWatchlistAction $isTitleInWatchlist;

    public Title $title;

    public bool $inWatchlist = false;

    public function boot(IsTitleInWatchlistAction $isTitleInWatchlist): void
    {
        $this->isTitleInWatchlist = $isTitleInWatchlist;
    }

    public function mount(Title $title): void
    {
        $this->title = $title;
        $this->refreshTrackingState();
    }

    public function toggle(ToggleWatchlistItemAction $toggleWatchlistItem): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $this->inWatchlist = $toggleWatchlistItem->handle(auth()->user(), $this->title);
        $this->title->refresh()->load('statistic');
        $this->dispatch('title-personal-tracking-updated');
    }

    #[On('title-personal-tracking-updated')]
    public function refreshTrackingState(): void
    {
        $this->inWatchlist = auth()->check()
            && $this->isTitleInWatchlist->handle(auth()->user(), $this->title);
    }

    public function render()
    {
        return view('livewire.titles.watchlist-toggle');
    }
}
