<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Frontend;

use Illuminate\Foundation\Http\FormRequest;

class ApplyDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
            'order_total' => ['required', 'integer', 'min:0'],
        ];
    }

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
