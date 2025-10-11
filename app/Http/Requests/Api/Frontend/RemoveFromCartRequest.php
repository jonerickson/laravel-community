<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Frontend;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class RemoveFromCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'price_id' => ['nullable', 'exists:prices,id'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'product_id.required' => 'Product is required.',
            'product_id.exists' => 'The selected product is invalid.',
            'price_id.exists' => 'The selected price is invalid.',
        ];
    }
}
