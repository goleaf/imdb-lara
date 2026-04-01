<?php

namespace App\Http\Requests\Admin;

use App\Models\Season;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSeasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        $season = $this->route('season');

        return $season instanceof Season
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
            'slug' => ['required', 'string', 'max:255', Rule::unique('seasons', 'slug')->ignore($this->season()->id)],
            'season_number' => ['required', 'integer', 'min:1', 'max:999'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'release_year' => ['nullable', 'integer', 'min:1888', 'max:2100'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function season(): Season
    {
        /** @var Season */
        return $this->route('season');
    }
}
