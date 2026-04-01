<?php

namespace App\Http\Requests\Admin;

use App\Models\Genre;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EditGenreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
        return [];
    }

    public function genre(): Genre
    {
        /** @var Genre */
        return $this->route('genre');
    }
}
