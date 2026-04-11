<?php

namespace App\Http\Requests\Admin;

use App\Models\AkaType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAkaTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $akaType = $this->route('akaType');

        return $akaType instanceof AkaType
            && ($this->user()?->can('update', $akaType) ?? false);
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
                Rule::unique($this->validationTable(), 'name')->ignore($this->akaType()->getKey()),
            ],
        ];
    }

    public function akaType(): AkaType
    {
        /** @var AkaType */
        return $this->route('akaType');
    }

    private function validationTable(): string
    {
        $akaType = new AkaType;

        return sprintf('%s.%s', $akaType->getConnectionName(), $akaType->getTable());
    }
}
