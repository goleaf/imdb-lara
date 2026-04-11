<?php

namespace App\Livewire\Lists;

use App\Actions\Lists\AttachTitleToUserListAction;
use App\Actions\Lists\BuildUserListItemsQueryAction;
use App\Actions\Lists\DeleteUserListAction;
use App\Actions\Lists\DetachTitleFromUserListAction;
use App\Actions\Lists\GetListTitleSuggestionsAction;
use App\Actions\Lists\MoveUserListItemAction;
use App\Actions\Lists\UpdateUserListAction;
use App\Enums\ListVisibility;
use App\Models\Title;
use App\Models\UserList;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ManageList extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    private const ITEMS_PER_PAGE = 12;

    protected AttachTitleToUserListAction $attachTitleToUserList;

    protected BuildUserListItemsQueryAction $buildUserListItemsQuery;

    protected DeleteUserListAction $deleteUserList;

    protected DetachTitleFromUserListAction $detachTitleFromUserList;

    protected GetListTitleSuggestionsAction $getListTitleSuggestions;

    protected MoveUserListItemAction $moveUserListItem;

    protected UpdateUserListAction $updateUserList;

    #[Locked]
    public UserList $list;

    public string $name = '';

    public string $description = '';

    #[Url(as: 'q')]
    public string $titleQuery = '';

    public string $visibility = ListVisibility::Private->value;

    public ?string $statusMessage = null;

    public function boot(
        AttachTitleToUserListAction $attachTitleToUserList,
        BuildUserListItemsQueryAction $buildUserListItemsQuery,
        DeleteUserListAction $deleteUserList,
        DetachTitleFromUserListAction $detachTitleFromUserList,
        GetListTitleSuggestionsAction $getListTitleSuggestions,
        MoveUserListItemAction $moveUserListItem,
        UpdateUserListAction $updateUserList,
    ): void {
        $this->attachTitleToUserList = $attachTitleToUserList;
        $this->buildUserListItemsQuery = $buildUserListItemsQuery;
        $this->deleteUserList = $deleteUserList;
        $this->detachTitleFromUserList = $detachTitleFromUserList;
        $this->getListTitleSuggestions = $getListTitleSuggestions;
        $this->moveUserListItem = $moveUserListItem;
        $this->updateUserList = $updateUserList;
    }

    public function mount(UserList $list): void
    {
        $this->authorize('update', $list);

        $this->list = $list;
        $this->name = $list->name;
        $this->description = $list->description ?? '';
        $this->visibility = $list->visibility->value;
    }

    public function saveList(): void
    {
        $this->authorize('update', $this->list);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'visibility' => [
                'required',
                Rule::in(array_map(
                    fn (ListVisibility $visibility): string => $visibility->value,
                    ListVisibility::cases(),
                )),
            ],
        ]);

        $this->list = $this->updateUserList->handle($this->list, $validated);
        $this->statusMessage = 'List details updated.';
    }

    public function addTitle(int $titleId): void
    {
        $this->authorize('update', $this->list);

        $title = Title::query()
            ->select(['id', 'slug'])
            ->published()
            ->findOrFail($titleId);

        $this->attachTitleToUserList->handle($this->list, $title);
        $this->list = $this->list->fresh() ?? $this->list;
        $this->titleQuery = '';
        $this->statusMessage = 'Title added to the list.';
        $this->resetItemsPage();
    }

    public function removeTitle(int $titleId): void
    {
        $this->authorize('update', $this->list);

        $this->detachTitleFromUserList->handle($this->list, $titleId);
        $this->list = $this->list->fresh() ?? $this->list;
        $this->statusMessage = 'Title removed from the list.';
    }

    public function sortItems(int $itemId, int $pagePosition): void
    {
        $this->authorize('update', $this->list);

        $currentPage = max(1, $this->getPage(pageName: 'items'));
        $absolutePosition = (($currentPage - 1) * self::ITEMS_PER_PAGE) + $pagePosition + 1;

        $this->moveUserListItem->reorder($this->list, $itemId, $absolutePosition);
        $this->statusMessage = 'List order updated.';
    }

    public function moveItemUp(int $itemId): void
    {
        $this->authorize('update', $this->list);

        $this->moveUserListItem->up($this->list, $itemId);
        $this->statusMessage = 'List order updated.';
    }

    public function moveItemDown(int $itemId): void
    {
        $this->authorize('update', $this->list);

        $this->moveUserListItem->down($this->list, $itemId);
        $this->statusMessage = 'List order updated.';
    }

    public function deleteList(): void
    {
        $this->authorize('delete', $this->list);

        $this->deleteUserList->handle($this->list);
        $this->redirectRoute('account.lists.index');
    }

    #[Computed]
    public function viewData(): array
    {
        $list = $this->list->fresh(['user:id,name,username']);

        abort_if(! $list instanceof UserList, 404);

        $list->loadCount('items');
        $this->list = $list;

        return [
            'items' => $this->buildUserListItemsQuery
                ->handle($list)
                ->simplePaginate(self::ITEMS_PER_PAGE, pageName: 'items')
                ->withQueryString(),
            'list' => $list,
            'titleSuggestions' => $this->getListTitleSuggestions->handle($list, $this->titleQuery),
            'visibilityOptions' => [
                ['value' => ListVisibility::Private->value, 'label' => 'Private'],
                ['value' => ListVisibility::Unlisted->value, 'label' => 'Unlisted'],
                ['value' => ListVisibility::Public->value, 'label' => 'Public'],
            ],
        ];
    }

    public function render(): View
    {
        return view('livewire.lists.manage-list', $this->viewData);
    }

    public function placeholder(): View
    {
        return view('livewire.placeholders.manage-list');
    }

    private function resetItemsPage(): void
    {
        $this->resetPage(pageName: 'items');
    }
}
