<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use App\Models\Price;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SubscriptionCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'price_id' => ['required', 'integer', 'exists:prices,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'price_id.required' => 'A product price must be selected.',
            'price_id.integer' => 'The price ID must be a valid number.',
            'price_id.exists' => 'The selected price does not exist.',
        ];
    }

    public function getPrice(): Price
    {
        return Price::findOrFail($this->validated('price_id'));
    }
}
