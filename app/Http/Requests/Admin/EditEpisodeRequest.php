<?php

namespace App\Http\Requests\Admin;

use App\Models\Episode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EditEpisodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $episode = $this->route('episode');

        return $episode instanceof Episode
            && ($this->user()?->can('update', $episode) ?? false);
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

    public function episode(): Episode
    {
        /** @var Episode */
        return $this->route('episode');
    }
}
