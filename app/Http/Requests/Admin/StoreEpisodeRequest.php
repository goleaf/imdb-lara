<?php

namespace App\Http\Requests\Admin;

use App\Enums\TitleType;
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
            && in_array($season->series?->title_type, [TitleType::Series, TitleType::MiniSeries], true)
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
            'episode' => ['required', 'array'],
            'episode.name' => ['required', 'string', 'max:255'],
            'episode.original_name' => ['nullable', 'string', 'max:255'],
            'episode.slug' => ['required', 'string', 'max:255', Rule::unique('titles', 'slug')],
            'episode.plot_outline' => ['nullable', 'string', 'max:1000'],
            'episode.synopsis' => ['nullable', 'string', 'max:10000'],
            'episode.release_year' => ['nullable', 'integer', 'min:1888', 'max:2100'],
            'episode.release_date' => ['nullable', 'date'],
            'episode.runtime_minutes' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'episode.age_rating' => ['nullable', 'string', 'max:32'],
            'episode.origin_country' => ['nullable', 'string', 'max:2'],
            'episode.original_language' => ['nullable', 'string', 'max:12'],
            'episode.tagline' => ['nullable', 'string', 'max:255'],
            'episode.meta_title' => ['nullable', 'string', 'max:255'],
            'episode.meta_description' => ['nullable', 'string', 'max:500'],
            'episode.search_keywords' => ['nullable', 'string', 'max:1000'],
            'episode.is_published' => ['required', 'boolean'],
            'episode.season_number' => ['nullable', 'integer', 'min:1', 'max:999'],
            'episode.episode_number' => ['nullable', 'integer', 'min:1', 'max:999'],
            'episode.absolute_number' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'episode.production_code' => ['nullable', 'string', 'max:64'],
            'episode.aired_at' => ['nullable', 'date'],
        ];
    }
}
