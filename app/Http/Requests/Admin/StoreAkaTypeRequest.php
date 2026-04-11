<?php

namespace App\Http\Requests\Admin;

use App\Models\AkaType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAkaTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AkaType::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:128', Rule::unique($this->validationTable(), 'name')],
        ];
    }

    private function validationTable(): string
    {
        $akaType = new AkaType;

        return sprintf('%s.%s', $akaType->getConnectionName(), $akaType->getTable());
    }
}
