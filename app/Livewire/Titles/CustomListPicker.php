<?php

namespace App\Livewire\Titles;

use App\Actions\Lists\BuildOwnedCustomListsQueryAction;
use App\Actions\Lists\CreateUserListAction;
use App\Actions\Lists\GetSelectedOwnedCustomListIdsAction;
use App\Actions\Lists\SyncTitleInUserListsAction;
use App\Enums\ListVisibility;
use App\Livewire\Forms\Lists\CreateUserListForm as CreateUserListDataForm;
use App\Models\Title;
use Livewire\Component;

class CustomListPicker extends Component
{
    protected BuildOwnedCustomListsQueryAction $buildOwnedCustomListsQuery;

    protected GetSelectedOwnedCustomListIdsAction $getSelectedOwnedCustomListIds;

    public Title $title;

    /**
     * @var list<string>
     */
    public array $selectedListIds = [];

    public string $listQuery = '';

    public CreateUserListDataForm $createListForm;

    public bool $showCreateListForm = false;

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

        $this->selectedListIds = $this->getSelectedOwnedCustomListIds->handle(auth()->user(), $title);
        $this->createListForm->visibility = ListVisibility::Private->value;
    }

    public function save(SyncTitleInUserListsAction $syncTitleInUserLists): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $selectedListIds = collect($this->selectedListIds)
            ->filter(fn (?string $value): bool => filled($value))
            ->map(fn ($value): int => (int) $value)
            ->unique()
            ->values()
            ->all();

        $syncTitleInUserLists->handle(auth()->user(), $this->title, $selectedListIds);

        $this->statusMessage = 'Custom lists updated.';
    }

    public function startCreatingList(): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $this->showCreateListForm = true;
        $this->createListForm->name = trim($this->listQuery);
    }

    public function cancelCreatingList(): void
    {
        $this->showCreateListForm = false;
        $this->createListForm->reset('name', 'description');
        $this->createListForm->visibility = ListVisibility::Private->value;
    }

    public function createList(CreateUserListAction $createUserList): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $list = $createUserList->handle(auth()->user(), $this->createListForm->payload());

        $this->selectedListIds = collect($this->selectedListIds)
            ->push((string) $list->id)
            ->unique()
            ->values()
            ->all();

        $this->listQuery = '';
        $this->cancelCreatingList();
        $this->statusMessage = 'List created. Click update lists to save this title.';
    }

    public function render()
    {
        return view('livewire.titles.custom-list-picker', [
            'lists' => auth()->check()
                ? $this->buildOwnedCustomListsQuery->handle(auth()->user(), $this->listQuery)->get()
                : collect(),
            'visibilityOptions' => [
                ['value' => ListVisibility::Private->value, 'label' => 'Private'],
                ['value' => ListVisibility::Unlisted->value, 'label' => 'Unlisted'],
                ['value' => ListVisibility::Public->value, 'label' => 'Public'],
            ],
        ]);
    }
}
