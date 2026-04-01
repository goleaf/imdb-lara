<?php

namespace App\Http\Requests\Admin;

use App\Models\PersonProfession;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePersonProfessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $profession = $this->route('profession');

        return $profession instanceof PersonProfession
            && ($this->user()?->can('update', $profession->person) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $profession = $this->route('profession');

        return [
            'department' => ['required', 'string', 'max:255'],
            'profession' => [
                'required',
                'string',
                'max:255',
                Rule::unique('person_professions', 'profession')
                    ->where('person_id', $profession?->person_id)
                    ->ignore($profession?->id),
            ],
            'is_primary' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }
}
