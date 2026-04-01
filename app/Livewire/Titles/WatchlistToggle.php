<?php

namespace App\Livewire\Titles;

use App\Actions\Lists\ToggleWatchlistItemAction;
use App\Models\Title;
use Livewire\Component;

class WatchlistToggle extends Component
{
    public Title $title;

    public bool $inWatchlist = false;

    public function mount(Title $title): void
    {
        $this->title = $title;
        $this->inWatchlist = auth()->check()
            && auth()->user()->watchlist?->items()->where('title_id', $title->id)->exists();
    }

    public function toggle(ToggleWatchlistItemAction $toggleWatchlistItem): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $this->inWatchlist = $toggleWatchlistItem->handle(auth()->user(), $this->title);
        $this->title->refresh()->load('statistic');
    }

    public function render()
    {
        return view('livewire.titles.watchlist-toggle');
    }
}
