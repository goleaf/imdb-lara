<?php

namespace App\Livewire\Titles;

use App\Actions\Lists\BuildOwnedCustomListsQueryAction;
use App\Actions\Lists\GetSelectedOwnedCustomListIdsAction;
use App\Actions\Lists\SyncTitleInUserListsAction;
use App\Models\Title;
use Livewire\Component;

class CustomListPicker extends Component
{
    protected BuildOwnedCustomListsQueryAction $buildOwnedCustomListsQuery;

    protected GetSelectedOwnedCustomListIdsAction $getSelectedOwnedCustomListIds;

    public Title $title;

    /**
     * @var array<int, bool>
     */
    public array $selectedLists = [];

    public ?string $statusMessage = null;

    public function boot(
        BuildOwnedCustomListsQueryAction $buildOwnedCustomListsQuery,
        GetSelectedOwnedCustomListIdsAction $getSelectedOwnedCustomListIds,
    ): void {
        $this->buildOwnedCustomListsQuery = $buildOwnedCustomListsQuery;
        $this->getSelectedOwnedCustomListIds = $getSelectedOwnedCustomListIds;
    }

    public function mount(Title $title): void
    {
        $this->title = $title;

        if (! auth()->check()) {
            return;
        }

        $this->selectedLists = $this->getSelectedOwnedCustomListIds->handle(auth()->user(), $title);
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
                ? $this->buildOwnedCustomListsQuery->handle(auth()->user())->get()
                : collect(),
        ]);
    }
}
