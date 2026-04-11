<?php

namespace App\Http\Requests\Admin;

use App\Models\AwardCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAwardCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $awardCategory = $this->route('awardCategory');

        return $awardCategory instanceof AwardCategory
            && ($this->user()?->can('update', $awardCategory) ?? false);
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
                'max:255',
                Rule::unique($this->validationTable(), 'name')->ignore($this->awardCategory()->getKey()),
            ],
        ];
    }

    public function awardCategory(): AwardCategory
    {
        /** @var AwardCategory */
        return $this->route('awardCategory');
    }

    private function validationTable(): string
    {
        $awardCategory = new AwardCategory;
        $connection = $awardCategory->getConnectionName();
        $table = $awardCategory->getTable();

        return filled($connection) ? sprintf('%s.%s', $connection, $table) : $table;
    }
}
