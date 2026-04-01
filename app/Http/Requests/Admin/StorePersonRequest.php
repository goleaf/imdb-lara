<?php

namespace App\Http\Requests\Admin;

use App\Models\Person;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Person::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'alternate_names' => ['nullable', 'string', 'max:1000'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('people', 'slug')],
            'short_biography' => ['nullable', 'string', 'max:2000'],
            'biography' => ['nullable', 'string', 'max:10000'],
            'known_for_department' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'death_date' => ['nullable', 'date', 'after_or_equal:birth_date'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'death_place' => ['nullable', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:120'],
            'popularity_rank' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'search_keywords' => ['nullable', 'string', 'max:1000'],
            'is_published' => ['required', 'boolean'],
        ];
    }
}
