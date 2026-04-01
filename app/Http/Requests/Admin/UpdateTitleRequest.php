<?php

namespace App\Http\Requests\Admin;

use App\Enums\TitleType;
use App\Models\Title;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $title = $this->route('title');

        return $title instanceof Title
            && ($this->user()?->can('update', $title) ?? false);
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
            'original_name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('titles', 'slug')->ignore($this->title()->id)],
            'title_type' => ['nullable', Rule::enum(TitleType::class)],
            'release_year' => ['nullable', 'integer', 'min:1888', 'max:2100'],
            'end_year' => ['nullable', 'integer', 'min:1888', 'max:2100', 'gte:release_year'],
            'release_date' => ['nullable', 'date'],
            'runtime_minutes' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'age_rating' => ['nullable', 'string', 'max:32'],
            'plot_outline' => ['nullable', 'string', 'max:1000'],
            'synopsis' => ['nullable', 'string', 'max:10000'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'origin_country' => ['nullable', 'string', 'max:32'],
            'original_language' => ['nullable', 'string', 'max:32'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'search_keywords' => ['nullable', 'string', 'max:1000'],
            'is_published' => ['required', 'boolean'],
            'genre_ids' => ['nullable', 'array'],
            'genre_ids.*' => ['integer', 'exists:genres,id'],
        ];
    }

    public function title(): Title
    {
        /** @var Title */
        return $this->route('title');
    }
}
