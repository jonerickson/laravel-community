<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use App\Models\Price;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;

class SubscriptionCancelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * @return array<string, string[]>
     */
    public function rules(): array
    {
        return [
            'price_id' => ['required', 'integer', 'exists:prices,id'],
            'immediate' => ['boolean'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'price_id.required' => 'A price ID is required to cancel the subscription.',
            'price_id.integer' => 'The price ID must be a valid number.',
            'price_id.exists' => 'The selected price does not exist.',
        ];
    }

    public function getPrice(): Price
    {
        return Price::findOrFail($this->validated('price_id'));
    }

    public function isImmediate(): bool
    {
        return (bool) $this->validated('immediate', false);
    }
}
