<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\CartData;
use App\Data\CartItemData;
use App\Enums\OrderStatus;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Policy;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use RuntimeException;
use Throwable;

class ShoppingCartService
{
    public function __construct(
        private readonly Request $request,
        private readonly DiscountService $discountService,
        #[CurrentUser]
        private readonly ?User $user = null,
    ) {
        //
    }

    public function getCart(): CartData
    {
        $cartItems = $this->getCartItems();

        return CartData::from([
            'cartCount' => count($cartItems),
            'cartItems' => CartItemData::collect($cartItems),
        ]);
    }

    public function getCartCount(): int
    {
        $order = $this->getOrCreatePendingOrder();

        if (! $order instanceof Order) {
            return 0;
        }

        return $order->items()->count();
    }

    public function clearCart(): void
    {
        $this->clearPendingOrder();
    }

    public function getOrCreatePendingOrder(): ?Order
    {
        if (! $this->user instanceof User) {
            return null;
        }

        $orderId = $this->request->session()->get('pending_order_id');

        if ($orderId) {
            $order = Order::query()
                ->where('id', $orderId)
                ->whereBelongsTo($this->user)
                ->where('status', OrderStatus::Pending)
                ->with(['items.price.product.inventoryItem', 'discounts'])
                ->first();

            if ($order) {
                return $order;
            }
        }

        $order = Order::create([
            'user_id' => $this->user->id,
            'status' => OrderStatus::Pending,
        ]);

        $this->request->session()->put('pending_order_id', $order->id);

        return $order->loadMissing(['discounts', 'items']);
    }

    public function clearPendingOrder(): void
    {
        $orderId = $this->request->session()->get('pending_order_id');

        if ($orderId && $this->user) {
            Order::query()
                ->where('id', $orderId)
                ->whereBelongsTo($this->user)
                ->where('status', OrderStatus::Pending)
                ->delete();

            $this->request->session()->forget('pending_order_id');
        }
    }

    /**
     * @throws Throwable
     */
    public function applyDiscount(Order $order, Discount $discount): void
    {
        if ($order->discounts()->where('discount_id', $discount->id)->exists()) {
            throw new RuntimeException('This discount has already been applied to your order.');
        }

        if (! $discount->type->canBeUsedAtCheckout()) {
            throw new RuntimeException('The discount provided cannot be used at checkout. Please provide another discount.');
        }

        if ($product = $order->items()->with('price.product')->get()->firstWhere('price.product.allow_discount_codes', false)) {
            throw new RuntimeException($product->name.' does not allow the use of a discount code. Please remove it from your shopping cart to use the discount code.');
        }

        if ($discount->user_id && $order->user_id !== $discount->user_id) {
            throw new RuntimeException('The discount you provided belongs to someone else. Please make sure to use a discount code assigned to your account.');
        }

        $discountAmount = $this->discountService->calculateDiscount($order, $discount);

        if ($discountAmount <= 0) {
            $minAmount = $discount->min_order_amount;
            if ($minAmount > 0 && $order->amount_subtotal < $minAmount) {
                throw new RuntimeException('The order subtotal must be at least '.Number::currency($discount->min_order_amount).' to use this discount.');
            }

            throw new RuntimeException('This discount cannot be applied to your order.');
        }

        $this->discountService->applyDiscountsToOrder($order, [$discount]);
    }

    public function removeDiscount(Order $order, int $discountId): void
    {
        $order->discounts()->detach($discountId);
    }

    public function addItem(int $priceId, int $quantity): CartData
    {
        $order = $this->getOrCreatePendingOrder();

        if (! $order instanceof Order) {
            return $this->getCart();
        }

        $order->items()->create([
            'price_id' => $priceId,
            'quantity' => $quantity,
        ]);

        return $this->getCart();
    }

    public function updateItem(int $priceId, int $quantity): CartData
    {
        $order = $this->getOrCreatePendingOrder();

        if (! $order instanceof Order) {
            return $this->getCart();
        }

        $order->items()
            ->where('price_id', '!=', $priceId)
            ->delete();

        $item = $order->items()
            ->where('price_id', $priceId)
            ->first();

        if ($item instanceof OrderItem) {
            $item->update(['quantity' => $quantity]);
        } else {
            $order->items()->create([
                'price_id' => $priceId,
                'quantity' => $quantity,
            ]);
        }

        return $this->getCart();
    }

    public function removeItem(int $priceId): CartData
    {
        $order = $this->getOrCreatePendingOrder();

        if ($order instanceof Order) {
            $order->items()
                ->where('price_id', $priceId)
                ->delete();
        }

        return $this->getCart();
    }

    /**
     * @return array<int, array{product_id: int, price_id: ?int, name: string, slug: string, quantity: int, product: ?Product, selected_price: ?Price, available_prices: Collection, added_at: mixed}>
     */
    private function getCartItems(): array
    {
        $order = $this->getOrCreatePendingOrder();

        if (! $order instanceof Order) {
            return [];
        }

        $orderItems = $order->items()->with([
            'price.product' => function ($query): void {
                $query
                    ->with(['defaultPrice', 'inventoryItem'])
                    ->with(['prices' => function (Price|HasMany $query): void {
                        $query->active()->visible()->orderBy('is_default', 'desc');
                    }])
                    ->with(['policies' => function (Policy|BelongsToMany $query): void {
                        $query->active()->effective()->orderBy('title');
                    }]);
            },
        ])->get();

        return $orderItems->map(fn (OrderItem $item): array => [
            'product_id' => $item->price->product_id,
            'price_id' => $item->price_id,
            'name' => $item->price->product?->name ?? $item->name,
            'slug' => $item->price->product?->slug ?? '',
            'quantity' => $item->quantity,
            'product' => $item->price->product,
            'selected_price' => $item->price,
            'available_prices' => $item->price->product?->prices ?? collect(),
            'added_at' => $item->created_at,
        ])->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values()->toArray();
    }
}
