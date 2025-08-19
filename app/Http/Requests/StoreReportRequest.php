<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ReportReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reportable_type' => ['required', 'string'],
            'reportable_id' => ['required', 'integer', 'min:1'],
            'reason' => ['required', Rule::enum(ReportReason::class)],
            'additional_info' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reportable_type.required' => 'The content type is required.',
            'reportable_id.required' => 'The content ID is required.',
            'reason.required' => 'Please select a reason for the report.',
            'additional_info.max' => 'Additional information cannot exceed 1000 characters.',
        ];
    }
}
