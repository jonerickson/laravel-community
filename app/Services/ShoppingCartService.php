<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\CartData;
use App\Data\CartItemData;
use App\Enums\OrderStatus;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use RuntimeException;

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
                ->with('items')
                ->with('discounts')
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

    public function applyDiscount(Order $order, Discount $discount): void
    {
        if ($order->discounts()->where('discount_id', $discount->id)->exists()) {
            throw new RuntimeException('This discount has already been applied to your order.');
        }

        $this->discountService->applyDiscountsToOrder($order, [$discount]);
    }

    public function removeDiscount(Order $order, int $discountId): void
    {
        $order->discounts()->detach($discountId);
    }

    public function addItem(int $productId, ?int $priceId, int $quantity): CartData
    {
        $order = $this->getOrCreatePendingOrder();

        if (! $order instanceof Order) {
            return $this->getCart();
        }

        Product::findOrFail($productId);

        $existingItem = $order->items()
            ->where('product_id', $productId)
            ->where('price_id', $priceId)
            ->first();

        if ($existingItem instanceof OrderItem) {
            $existingItem->update([
                'quantity' => $existingItem->quantity + $quantity,
            ]);
        } else {
            $order->items()->create([
                'product_id' => $productId,
                'price_id' => $priceId,
                'quantity' => $quantity,
            ]);
        }

        return $this->getCart();
    }

    public function updateItem(int $productId, ?int $priceId, int $quantity): CartData
    {
        $order = $this->getOrCreatePendingOrder();

        if (! $order instanceof Order) {
            return $this->getCart();
        }

        $order->items()
            ->where('product_id', $productId)
            ->where('price_id', '!=', $priceId)
            ->delete();

        $item = $order->items()
            ->where('product_id', $productId)
            ->where('price_id', $priceId)
            ->first();

        if ($item instanceof OrderItem) {
            $item->update(['quantity' => $quantity]);
        } else {
            $order->items()->create([
                'product_id' => $productId,
                'price_id' => $priceId,
                'quantity' => $quantity,
            ]);
        }

        return $this->getCart();
    }

    public function removeItem(int $productId, ?int $priceId): CartData
    {
        $order = $this->getOrCreatePendingOrder();

        if ($order instanceof Order) {
            $order->items()
                ->where('product_id', $productId)
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
            'product' => function ($query): void {
                $query->with('defaultPrice')
                    ->with(['prices' => function (HasMany $query): void {
                        $query->active()->orderBy('is_default', 'desc');
                    }])
                    ->with(['policies' => function (BelongsToMany $query): void {
                        $query->active()->effective()->orderBy('title');
                    }]);
            },
            'price',
        ])->get();

        return $orderItems->map(fn (OrderItem $item): array => [
            'product_id' => $item->product_id,
            'price_id' => $item->price_id,
            'name' => $item->product?->name ?? $item->name,
            'slug' => $item->product?->slug ?? '',
            'quantity' => $item->quantity,
            'product' => $item->product,
            'selected_price' => $item->price,
            'available_prices' => $item->product?->prices ?? collect(),
            'added_at' => $item->created_at,
        ])->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values()->toArray();
    }
}
