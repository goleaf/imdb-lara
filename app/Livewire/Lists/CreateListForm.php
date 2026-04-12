<?php

namespace App\Livewire\Lists;

use App\Actions\Lists\CreateUserListAction;
use App\Enums\ListVisibility;
use App\Livewire\Forms\Lists\CreateUserListForm as CreateUserListDataForm;
use App\Models\UserList;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CreateListForm extends Component
{
    use AuthorizesRequests;

    public CreateUserListDataForm $form;

    public ?string $statusMessage = null;

    public function save(CreateUserListAction $createUserList): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $this->authorize('create', UserList::class);

        $createUserList->handle(auth()->user(), $this->form->payload());

        $this->form->reset('name', 'description');
        $this->form->visibility = ListVisibility::Private->value;
        $this->statusMessage = 'List created.';
    }

    #[Computed]
    public function viewData(): array
    {
        return [
            'visibilityOptions' => array_map(
                static fn (ListVisibility $visibility): array => [
                    'value' => $visibility->value,
                    'label' => $visibility->label(),
                    'icon' => $visibility->icon(),
                ],
                ListVisibility::cases(),
            ),
        ];
    }

    public function render(): View
    {
        return view('livewire.lists.create-list-form', $this->viewData);
    }

    public function placeholder(): View
    {
        return view('livewire.placeholders.create-list-form');
    }
}
