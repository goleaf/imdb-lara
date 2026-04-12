<?php

namespace App\Livewire\Forms\Lists;

use Livewire\Attributes\Validate;
use Livewire\Form;

class CreateUserListForm extends Form
{
    #[Validate('required|string|max:120')]
    public string $name = '';

    #[Validate('nullable|string|max:500')]
    public string $description = '';

    #[Validate('required|in:public,unlisted,private')]
    public string $visibility = 'private';

    /**
     * @return array{name: string, description?: string|null, visibility: string}
     */
    public function payload(): array
    {
        return $this->validate();
    }
}
