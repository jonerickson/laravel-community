<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Frontend;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class UpdateCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'price_id' => ['nullable', 'exists:prices,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'price_id.exists' => 'The selected price is invalid.',
            'quantity.required' => 'Quantity is required.',
            'quantity.integer' => 'Quantity must be a valid number.',
            'quantity.min' => 'Quantity must be at least 1.',
            'quantity.max' => 'Quantity cannot exceed 99.',
        ];
    }
}
