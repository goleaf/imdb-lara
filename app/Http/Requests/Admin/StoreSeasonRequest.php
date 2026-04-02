<?php

namespace App\Http\Requests\Admin;

use App\Enums\TitleType;
use App\Models\Season;
use App\Models\Title;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSeasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        $title = $this->route('title');

        return $title instanceof Title
            && in_array($title->title_type, [TitleType::Series, TitleType::MiniSeries], true)
            && ($this->user()?->can('create', Season::class) ?? false)
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
            'season' => ['required', 'array'],
            'season.name' => ['required', 'string', 'max:255'],
            'season.slug' => ['required', 'string', 'max:255', Rule::unique('seasons', 'slug')],
            'season.season_number' => ['required', 'integer', 'min:1', 'max:999'],
            'season.summary' => ['nullable', 'string', 'max:5000'],
            'season.release_year' => ['nullable', 'integer', 'min:1888', 'max:2100'],
            'season.meta_title' => ['nullable', 'string', 'max:255'],
            'season.meta_description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
