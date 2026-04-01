<?php

namespace App\Http\Requests\Catalog;

use App\Models\Person;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ShowPersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('person') instanceof Person;
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

    public function person(): Person
    {
        /** @var Person */
        return $this->route('person');
    }
}
