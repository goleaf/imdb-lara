<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $report = $this->route('report');

        return $report instanceof Report
            && ($this->user()?->can('update', $report) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ReportStatus::class)],
            'content_action' => ['nullable', Rule::in(['none', 'hide_content'])],
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
            'suspend_owner' => ['nullable', 'boolean'],
        ];
    }
}
