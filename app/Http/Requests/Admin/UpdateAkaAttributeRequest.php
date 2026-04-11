<?php

namespace App\Http\Requests\Admin;

use App\Models\AkaAttribute;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAkaAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $akaAttribute = $this->route('akaAttribute');

        return $akaAttribute instanceof AkaAttribute
            && ($this->user()?->can('update', $akaAttribute) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:128',
                Rule::unique($this->validationTable(), 'name')->ignore($this->akaAttribute()->getKey()),
            ],
        ];
    }

    public function akaAttribute(): AkaAttribute
    {
        /** @var AkaAttribute */
        return $this->route('akaAttribute');
    }

    private function validationTable(): string
    {
        $akaAttribute = new AkaAttribute;

        return sprintf('%s.%s', $akaAttribute->getConnectionName(), $akaAttribute->getTable());
    }
}
