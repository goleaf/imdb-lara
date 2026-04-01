<?php

namespace App\Http\Requests\Admin;

use App\Models\MediaAsset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EditMediaAssetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $mediaAsset = $this->route('mediaAsset');

        return $mediaAsset instanceof MediaAsset
            && ($this->user()?->can('update', $mediaAsset) ?? false);
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

    public function mediaAsset(): MediaAsset
    {
        /** @var MediaAsset */
        return $this->route('mediaAsset');
    }
}
