<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use Illuminate\Contracts\Support\Responsable;
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

    public function listPrices(Product $product, array $filters = []): Collection;

    public function getInvoices(User $user, array $filters = []): Collection;

    public function createPaymentMethod(User $user, string $paymentMethodId);

    public function getPaymentMethods(User $user): Collection;

    public function updatePaymentMethod(User $user, string $paymentMethodId, bool $isDefault): bool;

    public function deletePaymentMethod(User $user, string $paymentMethodId): bool;

    public function startSubscription(User $user, Product $product, ProductPrice $price, string $returnUrl, bool $allowPromotionCodes = false, int $trialDays = 0): bool|Responsable;

    public function cancelSubscription(User $user, Product $product): bool;

    public function isSubscribedToProduct(User $user, Product $product, ?ProductPrice $price = null): bool;
}
