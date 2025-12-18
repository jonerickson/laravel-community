<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use App\Rules\UniqueCartItem;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'price_id' => ['required', 'exists:prices,id', new UniqueCartItem],
            'quantity' => ['integer', 'min:1', 'max:99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'price_id.required' => 'Please select a price option.',
            'price_id.exists' => 'The selected price is invalid.',
            'quantity.integer' => 'The quantity must be a valid number.',
            'quantity.min' => 'The quantity must be at least 1.',
            'quantity.max' => 'The quantity cannot exceed 99.',
        ];
    }
}
