<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Price;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ShoppingCartService
{
    public function __construct(private readonly Request $request)
    {
        //
    }

    public function getCartItems(): array
    {
        if (! $this->request->hasSession()) {
            return [];
        }

        $cart = $this->request->session()->get('shopping_cart', []);

        if (empty($cart)) {
            return [];
        }

        $productIds = array_unique(array_column($cart, 'product_id'));
        $priceIds = array_filter(array_column($cart, 'price_id'));

        $products = $this->getProducts($productIds);
        $prices = $this->getPrices($priceIds);

        return $this->mapCartItems($cart, $products, $prices);
    }

    public function getCartCount(): int
    {
        return count($this->getCartItems());
    }

    public function clearCart(): void
    {
        if (! $this->request->hasSession()) {
            return;
        }

        $this->request->session()->forget('shopping_cart');
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
