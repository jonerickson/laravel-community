<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class ShoppingCartService
{
    public function getCartItems(): array
    {
        $cart = Session::get('shopping_cart', []);

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
        Session::forget('shopping_cart');
    }

    private function getProducts(array $productIds): Collection
    {
        return Product::with(['prices' => function ($query): void {
            $query->where('is_active', true)->orderBy('is_default', 'desc');
        }, 'defaultPrice', 'policies' => function ($query): void {
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
            : ProductPrice::whereIn('id', $priceIds)->get()->keyBy('id');
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
