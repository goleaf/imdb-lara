<?php

namespace App\Http\Requests\Catalog;

use App\Models\Genre;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ShowGenreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('genre') instanceof Genre;
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
