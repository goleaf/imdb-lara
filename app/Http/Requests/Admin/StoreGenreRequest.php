<?php

namespace App\Http\Requests\Admin;

use App\Models\Genre;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGenreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Genre::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('genres', 'name')],
            'slug' => ['required', 'string', 'max:255', Rule::unique('genres', 'slug')],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
