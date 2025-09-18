<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\PaymentMethodData;
use App\Data\SubscriptionData;
use App\Models\Order;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

interface PaymentProcessor
{
    public function createProduct(Product $product): Product;

    public function getProduct(Product $product): Product;

    public function updateProduct(Product $product): Product;

    public function deleteProduct(Product $product): bool;

    public function listProducts(array $filters = []): Collection;

    public function createPrice(Product $product, Price $price): Price;

    public function updatePrice(Product $product, Price $price): Price;

    public function deletePrice(Product $product, Price $price): bool;

    public function listPrices(Product $product, array $filters = []): Collection;

    public function createPaymentMethod(User $user, string $paymentMethodId): PaymentMethodData;

    public function getPaymentMethods(User $user): Collection;

    public function updatePaymentMethod(User $user, string $paymentMethodId, bool $isDefault): bool;

    public function deletePaymentMethod(User $user, string $paymentMethodId): bool;

    public function startSubscription(User $user, Order $order, bool $chargeNow = true): bool|string;

    public function cancelSubscription(User $user, bool $cancelNow = false): bool;

    public function continueSubscription(User $user): bool;

    public function currentSubscription(User $user): ?SubscriptionData;

    public function redirectToCheckout(User $user, Order $order): bool|string;

    public function processCheckoutSuccess(Request $request, Order $order): bool;

    public function processCheckoutCancel(Request $request, Order $order): bool;
}
