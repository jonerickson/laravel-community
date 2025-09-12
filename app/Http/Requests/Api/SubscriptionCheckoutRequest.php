<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SubscriptionCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'price_id' => ['required', 'integer', 'exists:products_prices,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'A product must be selected.',
            'product_id.exists' => 'The selected product is not available.',
            'price_id.required' => 'A billing cycle must be selected.',
        ];
    }
}
