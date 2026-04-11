<?php

namespace App\Http\Requests\Admin;

use App\Models\AwardCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAwardCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AwardCategory::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique($this->validationTable(), 'name')],
        ];
    }

    private function validationTable(): string
    {
        $awardCategory = new AwardCategory;

        return sprintf('%s.%s', $awardCategory->getConnectionName(), $awardCategory->getTable());
    }
}
