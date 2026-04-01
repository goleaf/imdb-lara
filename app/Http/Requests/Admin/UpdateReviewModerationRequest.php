<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReviewStatus;
use App\Models\Review;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReviewModerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $review = $this->route('review');

        return $review instanceof Review
            && ($this->user()?->can('moderate', $review) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ReviewStatus::class)],
            'moderation_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
