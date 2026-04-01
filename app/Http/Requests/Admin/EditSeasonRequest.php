<?php

namespace App\Http\Requests\Admin;

use App\Models\Season;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EditSeasonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
        return [];
    }

    public function season(): Season
    {
        /** @var Season */
        return $this->route('season');
    }
}
