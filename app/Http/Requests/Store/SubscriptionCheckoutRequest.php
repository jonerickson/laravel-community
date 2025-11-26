<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use App\Models\Order;
use App\Models\Price;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;

class SubscriptionCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'price_id' => ['required', 'integer', 'exists:prices,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
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

    public function generateOrder(User $user): Order
    {
        $order = $user->orders()->create();
        $order->items()->create([
            'price_id' => $this->integer('price_id'),
        ]);

        return $order;
    }
}
