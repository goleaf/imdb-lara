<?php

namespace App\Livewire\Titles;

use App\Actions\Lists\SyncTitleInUserListsAction;
use App\Models\Title;
use App\Models\UserList;
use Livewire\Component;

class CustomListPicker extends Component
{
    public Title $title;

    /**
     * @var array<int, bool>
     */
    public array $selectedLists = [];

    public ?string $statusMessage = null;

    public function mount(Title $title): void
    {
        $this->title = $title;

        if (! auth()->check()) {
            return;
        }

        $selectedIds = UserList::query()
            ->select(['id'])
            ->whereBelongsTo(auth()->user())
            ->where('is_watchlist', false)
            ->whereHas('items', fn ($query) => $query->where('title_id', $title->id))
            ->pluck('id');

        $this->selectedLists = $selectedIds
            ->mapWithKeys(fn (int $listId): array => [$listId => true])
            ->all();
    }

    public function save(SyncTitleInUserListsAction $syncTitleInUserLists): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $selectedListIds = collect($this->selectedLists)
            ->filter()
            ->keys()
            ->map(fn ($value): int => (int) $value)
            ->values()
            ->all();

        $syncTitleInUserLists->handle(auth()->user(), $this->title, $selectedListIds);

        $this->statusMessage = 'Custom lists updated.';
    }

    public function render()
    {
        return view('livewire.titles.custom-list-picker', [
            'lists' => auth()->check()
                ? UserList::query()
                    ->select(['id', 'user_id', 'name', 'slug', 'visibility', 'is_watchlist'])
                    ->whereBelongsTo(auth()->user())
                    ->where('is_watchlist', false)
                    ->orderBy('name')
                    ->get()
                : collect(),
        ]);
    }
}
