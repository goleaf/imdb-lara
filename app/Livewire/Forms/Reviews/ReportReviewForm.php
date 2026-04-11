<?php

namespace App\Livewire\Forms\Reviews;

use App\Enums\ReportReason;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ReportReviewForm extends Form
{
    #[Validate]
    public string $reason = 'spoiler';

    #[Validate]
    public string $details = '';

    protected function rules(): array
    {
        return [
            'reason' => ['required', Rule::in(array_map(fn (ReportReason $reason): string => $reason->value, ReportReason::cases()))],
            'details' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array{reason: string, details?: string|null}
     */
    public function payload(): array
    {
        return $this->validate();
    }
}
