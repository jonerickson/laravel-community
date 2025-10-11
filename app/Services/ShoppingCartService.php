<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\CartData;
use App\Data\CartItemData;
use App\Enums\OrderStatus;
use App\Models\Discount;
use App\Models\Order;
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
        if (! $this->request->hasSession()) {
            return 0;
        }

        $cart = $this->getRawCart();

        return count($cart);
    }

    public function clearCart(): void
    {
        if (! $this->request->hasSession()) {
            return;
        }

        $this->request->session()->forget('shopping_cart');
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

        return $order->load('discounts');
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

    public function applyDiscount(Order $order, Discount $discount, int $orderTotal): void
    {
        if ($order->discounts()->where('discount_id', $discount->id)->exists()) {
            throw new RuntimeException('This discount has already been applied to your order.');
        }

        $discountAmount = $discount->calculateDiscount($orderTotal);

        $order->discounts()->attach($discount->id, [
            'amount_applied' => $discountAmount,
            'balance_before' => $orderTotal,
            'balance_after' => max(0, $orderTotal - $discountAmount),
        ]);
    }

    public function removeDiscount(Order $order, int $discountId): void
    {
        $order->discounts()->detach($discountId);
    }

    public function addItem(int $productId, ?int $priceId, int $quantity): CartData
    {
        $cart = $this->getRawCart();
        $cartKey = $productId.'_'.$priceId;

        $product = Product::findOrFail($productId);

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += $quantity;
        } else {
            $cart[$cartKey] = [
                'product_id' => $productId,
                'price_id' => $priceId,
                'name' => $product->name,
                'slug' => $product->slug,
                'quantity' => $quantity,
                'added_at' => now(),
            ];
        }

        $this->saveCart($cart);

        return $this->getCart();
    }

    public function updateItem(int $productId, ?int $priceId, int $quantity): CartData
    {
        $cart = $this->getRawCart();

        $existingKeys = array_filter(
            array_keys($cart),
            fn (int|string $key): bool => str_starts_with((string) $key, $productId.'_')
        );

        foreach ($existingKeys as $key) {
            unset($cart[$key]);
        }

        $product = Product::findOrFail($productId);
        $newCartKey = $productId.'_'.$priceId;

        $cart[$newCartKey] = [
            'product_id' => $productId,
            'price_id' => $priceId,
            'name' => $product->name,
            'slug' => $product->slug,
            'quantity' => $quantity,
            'added_at' => now(),
        ];

        $this->saveCart($cart);

        return $this->getCart();
    }

    public function removeItem(int $productId, ?int $priceId): CartData
    {
        $cart = $this->getRawCart();
        $cartKey = $productId.'_'.$priceId;

        if (isset($cart[$cartKey])) {
            unset($cart[$cartKey]);
            $this->saveCart($cart);
        }

        return $this->getCart();
    }

    /**
     * @return array<int, array{product_id: int, price_id: ?int, name: string, slug: string, quantity: int, product: ?Product, selected_price: ?Price, available_prices: Collection, added_at: mixed}>
     */
    private function getCartItems(): array
    {
        if (! $this->request->hasSession()) {
            return [];
        }

        $cart = $this->getRawCart();

        if ($cart === []) {
            return [];
        }

        $productIds = array_unique(array_column($cart, 'product_id'));
        $priceIds = array_filter(array_column($cart, 'price_id'));

        $products = $this->getProducts($productIds);
        $prices = $this->getPrices($priceIds);

        return $this->mapCartItems($cart, $products, $prices);
    }

    private function getRawCart(): array
    {
        if (! $this->request->hasSession()) {
            return [];
        }

        return $this->request->session()->get('shopping_cart', []);
    }

    private function saveCart(array $cart): void
    {
        if (! $this->request->hasSession()) {
            return;
        }

        $this->request->session()->put('shopping_cart', $cart);
    }

    private function getProducts(array $productIds): Collection
    {
        return Product::query()
            ->with('defaultPrice')
            ->with(['prices' => function (HasMany $query): void {
                $query->active()->orderBy('is_default', 'desc');
            }])
            ->with(['policies' => function (BelongsToMany $query): void {
                $query->active()->effective()->orderBy('title');
            }])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');
    }

    private function getPrices(array $priceIds): Collection
    {
        return blank($priceIds)
            ? collect()
            : Price::whereIn('id', $priceIds)->get()->keyBy('id');
    }

    private function mapCartItems(array $cart, Collection $products, Collection $prices): array
    {
        return collect($cart)->map(function (array $item) use ($products, $prices): array {
            $product = $products->get($item['product_id']);
            $selectedPrice = $item['price_id'] ? $prices->get($item['price_id']) : null;

            return [
                'product_id' => $item['product_id'],
                'price_id' => $item['price_id'],
                'name' => $product?->name ?? $item['name'],
                'slug' => $product?->slug ?? $item['slug'],
                'quantity' => $item['quantity'],
                'product' => $product,
                'selected_price' => $selectedPrice,
                'available_prices' => $product?->prices ?? collect(),
                'added_at' => $item['added_at'],
            ];
        })->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values()->toArray();
    }
}
