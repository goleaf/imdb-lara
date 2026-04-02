<?php

namespace App\Livewire\Lists;

use App\Actions\Lists\CreateUserListAction;
use App\Enums\ListVisibility;
use App\Livewire\Forms\Lists\CreateUserListForm as CreateUserListDataForm;
use Livewire\Component;

class CreateListForm extends Component
{
    public CreateUserListDataForm $form;

    public ?string $statusMessage = null;

    public function save(CreateUserListAction $createUserList): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $createUserList->handle(auth()->user(), $this->form->payload());

        $this->form->reset('name', 'description');
        $this->form->visibility = ListVisibility::Private->value;
        $this->statusMessage = 'List created.';
    }

    public function render()
    {
        return view('livewire.lists.create-list-form', [
            'visibilityOptions' => array_map(
                static fn (ListVisibility $visibility): array => [
                    'value' => $visibility->value,
                    'label' => $visibility->label(),
                    'icon' => $visibility->icon(),
                ],
                ListVisibility::cases(),
            ),
        ]);
    }
}
