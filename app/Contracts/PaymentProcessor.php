<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\Eloquent\Collection;

interface PaymentProcessor
{
    public function createProduct(Product $product): Product;

    public function getProduct(Product $product): Product;

    public function updateProduct(Product $product): Product;

    public function deleteProduct(Product $product): bool;

    public function listProducts(array $filters = []): Collection;

    public function createPrice(Product $product, ProductPrice $price): ProductPrice;

    public function updatePrice(Product $product, ProductPrice $price): ProductPrice;

    public function deletePrice(Product $product, ProductPrice $price): bool;
}
