<?php

namespace App\Http\Requests\Admin;

use App\Models\Genre;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGenreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $genre = $this->route('genre');

        return $genre instanceof Genre
            && ($this->user()?->can('update', $genre) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('genres', 'name')->ignore($this->genre()->id)],
            'slug' => ['required', 'string', 'max:255', Rule::unique('genres', 'slug')->ignore($this->genre()->id)],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function genre(): Genre
    {
        /** @var Genre */
        return $this->route('genre');
    }
}
