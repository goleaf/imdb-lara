<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContributionStatus;
use App\Models\Contribution;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contribution = $this->route('contribution');

        return $contribution instanceof Contribution
            && ($this->user()?->can('update', $contribution) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ContributionStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
