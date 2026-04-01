<?php

namespace App\Livewire\Forms\Lists;

use App\Enums\ListVisibility;
use Illuminate\Validation\Rule;
use Livewire\Form;

class CreateUserListForm extends Form
{
    public string $name = '';

    public string $description = '';

    public string $visibility = 'private';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'visibility' => ['required', Rule::in(array_map(fn (ListVisibility $visibility): string => $visibility->value, ListVisibility::cases()))],
        ];
    }

    /**
     * @return array{name: string, description?: string|null, visibility: string}
     */
    public function payload(): array
    {
        return $this->validate();
    }
}
