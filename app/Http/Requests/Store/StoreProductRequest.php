<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->can('view', $this->route('product'));
    }

    public function rules(): array
    {
        return [
            'price_id' => ['required', 'exists:prices,id'],
            'quantity' => ['integer', 'min:1', 'max:99'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'price_id.required' => 'Please select a price option.',
            'price_id.exists' => 'The selected price is invalid.',
            'quantity.integer' => 'Quantity must be a valid number.',
            'quantity.min' => 'Quantity must be at least 1.',
            'quantity.max' => 'Quantity cannot exceed 99.',
        ];
    }
}
