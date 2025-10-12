<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Frontend;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class RemoveDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'discount_id' => ['required', 'integer', 'exists:discounts,id'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'discount_id.required' => 'The discount ID is required.',
            'discount_id.integer' => 'The discount ID must be an integer.',
            'discount_id.exists' => 'The specified discount does not exist.',
        ];
    }
}
