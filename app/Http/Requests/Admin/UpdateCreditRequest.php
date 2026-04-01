<?php

namespace App\Http\Requests\Admin;

use App\Models\Credit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCreditRequest extends FormRequest
{
    public function authorize(): bool
    {
        $credit = $this->route('credit');

        return $credit instanceof Credit
            && ($this->user()?->can('update', $credit) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title_id' => ['required', 'integer', 'exists:titles,id'],
            'person_id' => ['required', 'integer', 'exists:people,id'],
            'person_profession_id' => ['nullable', 'integer', 'exists:person_professions,id'],
            'department' => ['required', 'string', 'max:255'],
            'job' => ['required', 'string', 'max:255'],
            'character_name' => ['nullable', 'string', 'max:255'],
            'billing_order' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'credited_as' => ['nullable', 'string', 'max:255'],
            'is_principal' => ['required', 'boolean'],
            'episode_id' => ['nullable', 'integer', 'exists:episodes,id'],
        ];
    }

    public function credit(): Credit
    {
        /** @var Credit */
        return $this->route('credit');
    }
}
