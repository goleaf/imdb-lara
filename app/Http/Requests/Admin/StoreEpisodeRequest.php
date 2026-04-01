<?php

namespace App\Http\Requests\Admin;

use App\Models\Episode;
use App\Models\Season;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEpisodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $season = $this->route('season');

        return $season instanceof Season
            && ($this->user()?->can('create', Episode::class) ?? false)
            && ($this->user()?->can('update', $season) ?? false);
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
            'slug' => ['required', 'string', 'max:255', Rule::unique('titles', 'slug')],
            'plot_outline' => ['nullable', 'string', 'max:1000'],
            'synopsis' => ['nullable', 'string', 'max:10000'],
            'release_year' => ['nullable', 'integer', 'min:1888', 'max:2100'],
            'release_date' => ['nullable', 'date'],
            'runtime_minutes' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'age_rating' => ['nullable', 'string', 'max:32'],
            'origin_country' => ['nullable', 'string', 'max:2'],
            'original_language' => ['nullable', 'string', 'max:12'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'search_keywords' => ['nullable', 'string', 'max:1000'],
            'is_published' => ['required', 'boolean'],
            'season_number' => ['nullable', 'integer', 'min:1', 'max:999'],
            'episode_number' => ['nullable', 'integer', 'min:1', 'max:999'],
            'absolute_number' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'production_code' => ['nullable', 'string', 'max:64'],
            'aired_at' => ['nullable', 'date'],
        ];
    }
}
