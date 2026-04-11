<?php

namespace App\Livewire\Forms\Titles;

use Livewire\Attributes\Validate;
use Livewire\Form;

class RatingForm extends Form
{
    #[Validate('required|integer|between:1,10')]
    public ?int $score = null;

    public function validatedScore(): int
    {
        return (int) $this->validate()['score'];
    }
}
