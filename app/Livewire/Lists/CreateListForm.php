<?php

namespace App\Livewire\Lists;

use App\Actions\Lists\CreateUserListAction;
use App\ListVisibility;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CreateListForm extends Component
{
    public string $name = '';

    public string $description = '';

    public string $visibility = 'private';

    public ?string $statusMessage = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'visibility' => ['required', Rule::in(array_map(fn (ListVisibility $visibility): string => $visibility->value, ListVisibility::cases()))],
        ];
    }

    public function save(CreateUserListAction $createUserList): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $validated = $this->validate();

        $createUserList->handle(auth()->user(), $validated);

        $this->reset('name', 'description');
        $this->visibility = ListVisibility::Private->value;
        $this->statusMessage = 'List created.';
    }

    public function render()
    {
        return view('livewire.lists.create-list-form');
    }
}
