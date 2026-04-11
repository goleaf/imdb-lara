<?php

namespace App\Livewire\Titles;

use App\Actions\Lists\BuildOwnedCustomListsQueryAction;
use App\Actions\Lists\CreateUserListAction;
use App\Actions\Lists\GetSelectedOwnedCustomListIdsAction;
use App\Actions\Lists\SyncTitleInUserListsAction;
use App\Enums\ListVisibility;
use App\Livewire\Forms\Lists\CreateUserListForm as CreateUserListDataForm;
use App\Models\Title;
use App\Models\UserList;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CustomListPicker extends Component
{
    use AuthorizesRequests;

    protected BuildOwnedCustomListsQueryAction $buildOwnedCustomListsQuery;

    protected GetSelectedOwnedCustomListIdsAction $getSelectedOwnedCustomListIds;

    #[Locked]
    public int $titleId;

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
        $this->titleId = $title->id;

        if (! auth()->check()) {
            return;
        }

        $this->selectedListIds = $this->getSelectedOwnedCustomListIds->handle(auth()->user(), $title);
        $this->createListForm->visibility = ListVisibility::Private->value;
    }

    public function save(SyncTitleInUserListsAction $syncTitleInUserLists): void
    {
        $user = auth()->user();

        if (! $user) {
            $this->redirectRoute('login');

            return;
        }

        $this->authorize('viewAny', UserList::class);

        $selectedListIds = collect($this->selectedListIds)
            ->filter(fn (?string $value): bool => filled($value))
            ->map(fn ($value): int => (int) $value)
            ->unique()
            ->values()
            ->all();

        $syncTitleInUserLists->handle($user, $this->title(), $selectedListIds);

        $this->statusMessage = 'Custom lists updated.';
    }

    public function startCreatingList(): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $this->authorize('create', UserList::class);

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
        $user = auth()->user();

        if (! $user) {
            $this->redirectRoute('login');

            return;
        }

        $this->authorize('create', UserList::class);

        $list = $createUserList->handle($user, $this->createListForm->payload());

        $this->selectedListIds = collect($this->selectedListIds)
            ->push((string) $list->id)
            ->unique()
            ->values()
            ->all();

        $this->listQuery = '';
        $this->cancelCreatingList();
        $this->statusMessage = 'List created. Click update lists to save this title.';
    }

    #[Computed]
    public function title(): Title
    {
        return Title::query()
            ->select(['id'])
            ->findOrFail($this->titleId);
    }

    /**
     * @return EloquentCollection<int, UserList>
     */
    #[Computed]
    public function lists(): EloquentCollection
    {
        if (! auth()->check()) {
            return new EloquentCollection;
        }

        return $this->buildOwnedCustomListsQuery
            ->handle(auth()->user(), $this->listQuery)
            ->get();
    }

    /**
     * @return EloquentCollection<int, UserList>
     */
    #[Computed]
    public function selectedLists(): EloquentCollection
    {
        if (! auth()->check()) {
            return new EloquentCollection;
        }

        $selectedListIds = collect($this->selectedListIds)
            ->map(fn (string $listId): int => (int) $listId)
            ->filter(fn (int $listId): bool => $listId > 0)
            ->values()
            ->all();

        if ($selectedListIds === []) {
            return new EloquentCollection;
        }

        return $this->buildOwnedCustomListsQuery
            ->handle(auth()->user())
            ->whereIn('id', $selectedListIds)
            ->get();
    }

    /**
     * @return list<array{value: string, label: string, icon: string}>
     */
    #[Computed]
    public function visibilityOptions(): array
    {
        return array_map(
            static fn (ListVisibility $visibility): array => [
                'value' => $visibility->value,
                'label' => $visibility->label(),
                'icon' => $visibility->icon(),
            ],
            ListVisibility::cases(),
        );
    }

    public function render(): View
    {
        return view('livewire.titles.custom-list-picker');
    }
}
