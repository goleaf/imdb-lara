<?php

namespace App\Livewire\Forms\Titles;

use Livewire\Form;

class ReviewForm extends Form
{
    public string $headline = '';

    public string $body = '';

    public bool $containsSpoilers = false;

    protected function rules(): array
    {
        return [
            'headline' => ['nullable', 'string', 'max:160'],
            'body' => ['required', 'string', 'min:20'],
            'containsSpoilers' => ['boolean'],
        ];
    }

    /**
     * @return array{headline: string, body: string, contains_spoilers: bool}
     */
    public function payload(): array
    {
        $validated = $this->validate();

        return [
            'headline' => $validated['headline'],
            'body' => $validated['body'],
            'contains_spoilers' => (bool) $validated['containsSpoilers'],
        ];
    }
}
