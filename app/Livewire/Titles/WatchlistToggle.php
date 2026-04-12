<?php

namespace App\Livewire\Titles;

use App\Actions\Lists\IsTitleInWatchlistAction;
use App\Actions\Lists\ToggleWatchlistItemAction;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class WatchlistToggle extends Component
{
    use AuthorizesRequests;

    protected IsTitleInWatchlistAction $isTitleInWatchlist;

    #[Locked]
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

        $this->authorize('track', $this->title);

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

    #[Computed]
    public function viewData(): array
    {
        return [
            'buttonIcon' => $this->inWatchlist ? 'bookmark-square' : 'bookmark',
            'buttonVariant' => $this->inWatchlist ? 'outline' : 'primary',
            'noticeIcon' => $this->inWatchlist ? 'check-circle' : 'information-circle',
            'noticeVariant' => $this->inWatchlist ? 'success' : 'info',
        ];
    }

    public function render(): View
    {
        return view('livewire.titles.watchlist-toggle', $this->viewData);
    }
}
