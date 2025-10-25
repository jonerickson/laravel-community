<?php

declare(strict_types=1);

namespace App\Rules;

use App\Services\ShoppingCartService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueCartItem implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cartService = app(ShoppingCartService::class);

        $order = $cartService->getOrCreatePendingOrder();

        if (! $order) {
            return;
        }

        $existingItem = $order->items()
            ->where('price_id', $value)
            ->first();

        if ($existingItem) {
            $fail('This item is already in your cart. Please adjust the quantity instead.');
        }
    }
}
