<?php

namespace App\Http\Requests\Admin;

use App\Models\Title;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateTitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Title::class) ?? false;
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
}
