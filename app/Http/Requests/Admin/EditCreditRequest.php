<?php

namespace App\Http\Requests\Admin;

use App\Models\Credit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EditCreditRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
        return [];
    }

    public function credit(): Credit
    {
        /** @var Credit */
        return $this->route('credit');
    }
}
