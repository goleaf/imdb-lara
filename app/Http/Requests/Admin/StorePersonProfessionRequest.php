<?php

namespace App\Http\Requests\Admin;

use App\Models\Person;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonProfessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $person = $this->route('person');

        return $person instanceof Person
            && ($this->user()?->can('update', $person) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $person = $this->route('person');

        return [
            'department' => ['required', 'string', 'max:255'],
            'profession' => [
                'required',
                'string',
                'max:255',
                Rule::unique('person_professions', 'profession')->where('person_id', $person?->id),
            ],
            'is_primary' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }
}
