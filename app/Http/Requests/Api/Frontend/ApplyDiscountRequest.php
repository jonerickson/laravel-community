<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Frontend;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class ApplyDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string[]>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
            'order_total' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'code.required' => 'A discount code is required.',
            'code.string' => 'The discount code must be a valid string.',
            'order_total.required' => 'The order total is required.',
            'order_total.integer' => 'The order total must be an integer.',
            'order_total.min' => 'The order total must be at least 0.',
        ];
    }
}
