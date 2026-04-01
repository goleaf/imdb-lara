<?php

namespace App\Http\Requests\Admin;

use App\Models\Title;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EditTitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $title = $this->route('title');

        return $title instanceof Title
            && ($this->user()?->can('update', $title) ?? false);
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

    public function title(): Title
    {
        /** @var Title */
        return $this->route('title');
    }
}
