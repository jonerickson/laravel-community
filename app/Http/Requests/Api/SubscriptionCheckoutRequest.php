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
            'price_id' => ['required', 'integer', 'exists:products_prices,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'price_id.required' => 'A product price must be selected.',
        ];
    }
}
