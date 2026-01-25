<?php

declare(strict_types=1);

use App\Drivers\Payments\NullDriver;
use App\Drivers\Payments\PaymentProcessor;
use App\Enums\OrderRefundReason;
use App\Enums\PaymentBehavior;
use App\Enums\ProrationBehavior;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Http\Request;

describe('NullDriver for Payments', function (): void {
    beforeEach(function (): void {
        $this->driver = new NullDriver;
    });

    test('implements PaymentProcessor interface', function (): void {
        expect($this->driver)->toBeInstanceOf(PaymentProcessor::class);
    });

    describe('Product methods', function (): void {
        test('createProduct returns null', function (): void {
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create();
            $product->categories()->attach($category);

            $result = $this->driver->createProduct($product);

            expect($result)->toBeNull();
        });

        test('getProduct returns null', function (): void {
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create();
            $product->categories()->attach($category);

            $result = $this->driver->getProduct($product);

            expect($result)->toBeNull();
        });

        test('updateProduct returns null', function (): void {
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create();
            $product->categories()->attach($category);

            $result = $this->driver->updateProduct($product);

            expect($result)->toBeNull();
        });

        test('deleteProduct returns false', function (): void {
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create();
            $product->categories()->attach($category);

            $result = $this->driver->deleteProduct($product);

            expect($result)->toBeFalse();
        });

        test('listProducts returns empty collection', function (): void {
            $result = $this->driver->listProducts();

            expect($result)->toBeInstanceOf(Illuminate\Support\Collection::class);
            expect($result)->toBeEmpty();
        });

        test('listProducts returns empty collection with filters', function (): void {
            $result = $this->driver->listProducts(['active' => true]);

            expect($result)->toBeInstanceOf(Illuminate\Support\Collection::class);
            expect($result)->toBeEmpty();
        });
    });

    describe('Price methods', function (): void {
        test('createPrice returns null', function (): void {
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create();
            $product->categories()->attach($category);
            $price = Price::factory()->create(['product_id' => $product->id]);

            $result = $this->driver->createPrice($price);

            expect($result)->toBeNull();
        });

        test('updatePrice returns null', function (): void {
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create();
            $product->categories()->attach($category);
            $price = Price::factory()->create(['product_id' => $product->id]);

            $result = $this->driver->updatePrice($price);

            expect($result)->toBeNull();
        });

        test('changePrice returns null', function (): void {
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create();
            $product->categories()->attach($category);
            $price = Price::factory()->create(['product_id' => $product->id]);

            $result = $this->driver->changePrice($price);

            expect($result)->toBeNull();
        });

        test('deletePrice returns false', function (): void {
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create();
            $product->categories()->attach($category);
            $price = Price::factory()->create(['product_id' => $product->id]);

            $result = $this->driver->deletePrice($price);

            expect($result)->toBeFalse();
        });

        test('listPrices returns empty collection', function (): void {
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create();
            $product->categories()->attach($category);

            $result = $this->driver->listPrices($product);

            expect($result)->toBeInstanceOf(Illuminate\Support\Collection::class);
            expect($result)->toBeEmpty();
        });
    });

    describe('Invoice methods', function (): void {
        test('findInvoice returns null', function (): void {
            $result = $this->driver->findInvoice('inv_123');

            expect($result)->toBeNull();
        });

        test('findInvoice returns null with params', function (): void {
            $result = $this->driver->findInvoice('inv_123', ['expand' => ['lines']]);

            expect($result)->toBeNull();
        });
    });

    describe('Payment method methods', function (): void {
        test('createPaymentMethod returns null', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->createPaymentMethod($user, 'pm_123');

            expect($result)->toBeNull();
        });

        test('listPaymentMethods returns empty collection', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->listPaymentMethods($user);

            expect($result)->toBeInstanceOf(Illuminate\Support\Collection::class);
            expect($result)->toBeEmpty();
        });

        test('updatePaymentMethod returns null', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->updatePaymentMethod($user, 'pm_123', true);

            expect($result)->toBeNull();
        });

        test('deletePaymentMethod returns false', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->deletePaymentMethod($user, 'pm_123');

            expect($result)->toBeFalse();
        });
    });

    describe('Customer methods', function (): void {
        test('searchCustomer returns null', function (): void {
            $result = $this->driver->searchCustomer('email', 'test@example.com');

            expect($result)->toBeNull();
        });

        test('createCustomer returns false', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->createCustomer($user);

            expect($result)->toBeFalse();
        });

        test('createCustomer returns false with force flag', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->createCustomer($user, true);

            expect($result)->toBeFalse();
        });

        test('getCustomer returns null', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->getCustomer($user);

            expect($result)->toBeNull();
        });

        test('deleteCustomer returns false', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->deleteCustomer($user);

            expect($result)->toBeFalse();
        });

        test('syncCustomerInformation returns false', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->syncCustomerInformation($user);

            expect($result)->toBeFalse();
        });

        test('getBillingPortalUrl returns null', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->getBillingPortalUrl($user);

            expect($result)->toBeNull();
        });
    });

    describe('Coupon methods', function (): void {
        test('createCoupon returns null', function (): void {
            $discount = Discount::factory()->promoCode()->create();

            $result = $this->driver->createCoupon($discount);

            expect($result)->toBeNull();
        });
    });

    describe('Subscription methods', function (): void {
        test('startSubscription returns false', function (): void {
            $user = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $result = $this->driver->startSubscription($order);

            expect($result)->toBeFalse();
        });

        test('startSubscription returns false with all parameters', function (): void {
            $user = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $result = $this->driver->startSubscription(
                order: $order,
                chargeNow: false,
                firstParty: false,
                prorationBehavior: ProrationBehavior::AlwaysInvoice,
                paymentBehavior: PaymentBehavior::ErrorIfIncomplete,
                backdateStartDate: now()->subDays(7),
                billingCycleAnchor: now()->addDays(15),
                successUrl: 'https://example.com/success',
                cancelUrl: 'https://example.com/cancel',
                customerOptions: ['tax_id' => 'vat_123'],
                subscriptionOptions: ['description' => 'Test subscription']
            );

            expect($result)->toBeFalse();
        });

        test('swapSubscription returns false', function (): void {
            $user = User::factory()->create();
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create();
            $product->categories()->attach($category);
            $price = Price::factory()->create(['product_id' => $product->id]);

            $result = $this->driver->swapSubscription($user, $price);

            expect($result)->toBeFalse();
        });

        test('swapSubscription returns false with all parameters', function (): void {
            $user = User::factory()->create();
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create();
            $product->categories()->attach($category);
            $price = Price::factory()->create(['product_id' => $product->id]);

            $result = $this->driver->swapSubscription(
                user: $user,
                price: $price,
                prorationBehavior: ProrationBehavior::None,
                paymentBehavior: PaymentBehavior::AllowIncomplete,
                options: ['billing_cycle_anchor' => 'now']
            );

            expect($result)->toBeFalse();
        });

        test('cancelSubscription returns false', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->cancelSubscription($user);

            expect($result)->toBeFalse();
        });

        test('cancelSubscription returns false with cancelNow and reason', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->cancelSubscription($user, true, 'User requested cancellation');

            expect($result)->toBeFalse();
        });

        test('continueSubscription returns false', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->continueSubscription($user);

            expect($result)->toBeFalse();
        });

        test('updateSubscription returns null', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->updateSubscription($user, ['cancel_at_period_end' => false]);

            expect($result)->toBeNull();
        });

        test('currentSubscription returns null', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->currentSubscription($user);

            expect($result)->toBeNull();
        });

        test('listSubscriptions returns empty collection', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->listSubscriptions($user);

            expect($result)->toBeInstanceOf(Illuminate\Support\Collection::class);
            expect($result)->toBeEmpty();
        });

        test('listSubscriptions returns empty collection with filters', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->listSubscriptions($user, ['status' => 'active']);

            expect($result)->toBeInstanceOf(Illuminate\Support\Collection::class);
            expect($result)->toBeEmpty();
        });

        test('listSubscribers returns empty collection', function (): void {
            $result = $this->driver->listSubscribers();

            expect($result)->toBeInstanceOf(Illuminate\Support\Collection::class);
            expect($result)->toBeEmpty();
        });

        test('listSubscribers returns empty collection with price filter', function (): void {
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create();
            $product->categories()->attach($category);
            $price = Price::factory()->create(['product_id' => $product->id]);

            $result = $this->driver->listSubscribers($price);

            expect($result)->toBeInstanceOf(Illuminate\Support\Collection::class);
            expect($result)->toBeEmpty();
        });
    });

    describe('Checkout methods', function (): void {
        test('getCheckoutUrl returns false', function (): void {
            $user = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $result = $this->driver->getCheckoutUrl($order);

            expect($result)->toBeFalse();
        });

        test('processCheckoutSuccess returns false', function (): void {
            $user = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);
            $request = Request::create('/checkout/success');

            $result = $this->driver->processCheckoutSuccess($request, $order);

            expect($result)->toBeFalse();
        });

        test('processCheckoutCancel returns false', function (): void {
            $user = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);
            $request = Request::create('/checkout/cancel');

            $result = $this->driver->processCheckoutCancel($request, $order);

            expect($result)->toBeFalse();
        });
    });

    describe('Order methods', function (): void {
        test('refundOrder returns false', function (): void {
            $user = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $result = $this->driver->refundOrder($order, OrderRefundReason::RequestedByCustomer);

            expect($result)->toBeFalse();
        });

        test('refundOrder returns false with notes', function (): void {
            $user = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $result = $this->driver->refundOrder($order, OrderRefundReason::Duplicate, 'Duplicate order refund');

            expect($result)->toBeFalse();
        });

        test('cancelOrder returns false', function (): void {
            $user = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $result = $this->driver->cancelOrder($order);

            expect($result)->toBeFalse();
        });
    });
});
