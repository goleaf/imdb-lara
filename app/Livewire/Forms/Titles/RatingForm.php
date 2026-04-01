<?php

namespace App\Livewire\Forms\Titles;

use Livewire\Form;

class RatingForm extends Form
{
    public ?int $score = null;

    protected function rules(): array
    {
        return [
            'score' => ['required', 'integer', 'between:1,10'],
        ];
    }

    public function validatedScore(): int
    {
        return (int) $this->validate()['score'];
    }
}
