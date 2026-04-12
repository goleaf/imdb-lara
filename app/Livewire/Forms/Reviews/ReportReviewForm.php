<?php

namespace App\Livewire\Forms\Reviews;

use Livewire\Attributes\Validate;
use Livewire\Form;

class ReportReviewForm extends Form
{
    #[Validate('required|in:spam,abuse,spoiler,harassment,inaccurate')]
    public string $reason = 'spoiler';

    #[Validate('nullable|string|max:1000')]
    public string $details = '';

    /**
     * @return array{reason: string, details?: string|null}
     */
    public function payload(): array
    {
        return $this->validate();
    }
}
