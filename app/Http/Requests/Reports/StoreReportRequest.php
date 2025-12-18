<?php

declare(strict_types=1);

namespace App\Http\Requests\Reports;

use App\Enums\ReportReason;
use App\Rules\BlacklistRule;
use App\Rules\NoProfanity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Override;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'reportable_type' => ['required', 'string'],
            'reportable_id' => ['required', 'integer', 'min:1'],
            'reason' => ['required', Rule::enum(ReportReason::class)],
            'additional_info' => ['nullable', 'string', 'max:1000', new NoProfanity, new BlacklistRule],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'reportable_type.required' => 'The content type is required.',
            'reportable_id.required' => 'The content ID is required.',
            'reason.required' => 'Please select a reason for the report.',
            'additional_info.max' => 'The additional information cannot exceed 1,000 characters.',
        ];
    }
}
