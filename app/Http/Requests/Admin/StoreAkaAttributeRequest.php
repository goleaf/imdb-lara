<?php

namespace App\Http\Requests\Admin;

use App\Models\AkaAttribute;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAkaAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AkaAttribute::class) ?? false;
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
        $akaAttribute = new AkaAttribute;

        return sprintf('%s.%s', $akaAttribute->getConnectionName(), $akaAttribute->getTable());
    }
}
