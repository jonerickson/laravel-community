<?php

declare(strict_types=1);

use App\Drivers\Payments\PaymentProcessor;
use App\Drivers\Payments\StripeDriver;
use App\Enums\DiscountValueType;
use App\Enums\OrderRefundReason;
use App\Enums\OrderStatus;
use App\Enums\PaymentBehavior;
use App\Enums\ProrationBehavior;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

describe('StripeDriver for Payments', function (): void {
    beforeEach(function (): void {
        Http::preventStrayRequests();

        $this->driver = new StripeDriver(config('cashier.secret', 'sk_test_fake'));
    });

    test('implements PaymentProcessor interface', function (): void {
        expect($this->driver)->toBeInstanceOf(PaymentProcessor::class);
    });

    describe('Product methods with Http::preventStrayRequests', function (): void {
        test('createProduct returns null when Http prevents stray requests', function (): void {
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create();
            $product->categories()->attach($category);

            $result = $this->driver->createProduct($product);

            expect($result)->toBeNull();
        });

        test('getProduct returns null when Http prevents stray requests', function (): void {
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create(['external_product_id' => 'prod_123']);
            $product->categories()->attach($category);

            $result = $this->driver->getProduct($product);

            expect($result)->toBeNull();
        });

        test('updateProduct returns null when Http prevents stray requests', function (): void {
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create(['external_product_id' => 'prod_123']);
            $product->categories()->attach($category);
            Price::factory()->create([
                'product_id' => $product->id,
                'is_default' => true,
                'external_price_id' => 'price_123',
            ]);

            $result = $this->driver->updateProduct($product);

            expect($result)->toBeNull();
        });

        test('deleteProduct returns false when Http prevents stray requests', function (): void {
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create(['external_product_id' => 'prod_123']);
            $product->categories()->attach($category);

            $result = $this->driver->deleteProduct($product);

            expect($result)->toBeFalse();
        });

        test('listProducts returns null when Http prevents stray requests', function (): void {
            $result = $this->driver->listProducts();

            expect($result)->toBeNull();
        });
    });

    describe('Price methods with Http::preventStrayRequests', function (): void {
        test('createPrice returns null when Http prevents stray requests', function (): void {
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create(['external_product_id' => 'prod_123']);
            $product->categories()->attach($category);
            $price = Price::factory()->create(['product_id' => $product->id]);

            $result = $this->driver->createPrice($price);

            expect($result)->toBeNull();
        });

        test('updatePrice returns null when Http prevents stray requests', function (): void {
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create(['external_product_id' => 'prod_123']);
            $product->categories()->attach($category);
            $price = Price::factory()->create([
                'product_id' => $product->id,
                'external_price_id' => 'price_123',
            ]);

            $result = $this->driver->updatePrice($price);

            expect($result)->toBeNull();
        });

        test('changePrice returns null when Http prevents stray requests', function (): void {
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create(['external_product_id' => 'prod_123']);
            $product->categories()->attach($category);
            $price = Price::factory()->create([
                'product_id' => $product->id,
                'external_price_id' => 'price_123',
            ]);

            $result = $this->driver->changePrice($price);

            expect($result)->toBeNull();
        });

        test('deletePrice returns false when Http prevents stray requests', function (): void {
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create(['external_product_id' => 'prod_123']);
            $product->categories()->attach($category);
            $price = Price::factory()->create([
                'product_id' => $product->id,
                'external_price_id' => 'price_123',
            ]);

            $result = $this->driver->deletePrice($price);

            expect($result)->toBeFalse();
        });

        test('listPrices returns null when Http prevents stray requests', function (): void {
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create(['external_product_id' => 'prod_123']);
            $product->categories()->attach($category);

            $result = $this->driver->listPrices($product);

            expect($result)->toBeNull();
        });
    });

    describe('Invoice methods with Http::preventStrayRequests', function (): void {
        test('findInvoice returns null when Http prevents stray requests', function (): void {
            $result = $this->driver->findInvoice('inv_123');

            expect($result)->toBeNull();
        });

        test('findInvoice returns null with params when Http prevents stray requests', function (): void {
            $result = $this->driver->findInvoice('inv_123', ['expand' => ['lines']]);

            expect($result)->toBeNull();
        });
    });

    describe('Payment method methods with Http::preventStrayRequests', function (): void {
        test('createPaymentMethod returns null when Http prevents stray requests', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->createPaymentMethod($user, 'pm_123');

            expect($result)->toBeNull();
        });

        test('listPaymentMethods returns null when Http prevents stray requests', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->listPaymentMethods($user);

            expect($result)->toBeNull();
        });

        test('updatePaymentMethod returns null when Http prevents stray requests', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->updatePaymentMethod($user, 'pm_123', true);

            expect($result)->toBeNull();
        });

        test('deletePaymentMethod returns false when Http prevents stray requests', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->deletePaymentMethod($user, 'pm_123');

            expect($result)->toBeFalse();
        });
    });

    describe('Customer methods with Http::preventStrayRequests', function (): void {
        test('searchCustomer returns null when Http prevents stray requests', function (): void {
            $result = $this->driver->searchCustomer('email', 'test@example.com');

            expect($result)->toBeNull();
        });

        test('createCustomer returns false when Http prevents stray requests', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->createCustomer($user);

            expect($result)->toBeFalse();
        });

        test('createCustomer returns false with force flag when Http prevents stray requests', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->createCustomer($user, true);

            expect($result)->toBeFalse();
        });

        test('getCustomer returns null when Http prevents stray requests', function (): void {
            $user = User::factory()->create(['stripe_id' => 'cus_123']);

            $result = $this->driver->getCustomer($user);

            expect($result)->toBeNull();
        });

        test('getCustomer returns null when user has no stripe id', function (): void {
            $user = User::factory()->create(['stripe_id' => null]);

            $result = $this->driver->getCustomer($user);

            expect($result)->toBeNull();
        });

        test('deleteCustomer returns false when Http prevents stray requests', function (): void {
            $user = User::factory()->create(['stripe_id' => 'cus_123']);

            $result = $this->driver->deleteCustomer($user);

            expect($result)->toBeFalse();
        });

        test('deleteCustomer returns false when user has no stripe id', function (): void {
            $user = User::factory()->create(['stripe_id' => null]);

            $result = $this->driver->deleteCustomer($user);

            expect($result)->toBeFalse();
        });

        test('syncCustomerInformation returns false when Http prevents stray requests', function (): void {
            $user = User::factory()->create(['stripe_id' => 'cus_123']);

            $result = $this->driver->syncCustomerInformation($user);

            expect($result)->toBeFalse();
        });

        test('getBillingPortalUrl returns null when user has no stripe id', function (): void {
            $user = User::factory()->create(['stripe_id' => null]);

            $result = $this->driver->getBillingPortalUrl($user);

            expect($result)->toBeNull();
        });

        test('getBillingPortalUrl returns null when Http prevents stray requests', function (): void {
            $user = User::factory()->create(['stripe_id' => 'cus_123']);

            $result = $this->driver->getBillingPortalUrl($user);

            expect($result)->toBeNull();
        });
    });

    describe('Coupon methods with Http::preventStrayRequests', function (): void {
        test('createCoupon returns null when Http prevents stray requests', function (): void {
            $discount = Discount::factory()->promoCode()->create();

            $result = $this->driver->createCoupon($discount);

            expect($result)->toBeNull();
        });

        test('createCoupon returns null for percentage discount when Http prevents stray requests', function (): void {
            $discount = Discount::factory()->promoCode()->create([
                'discount_type' => DiscountValueType::Percentage,
                'value' => 25,
            ]);

            $result = $this->driver->createCoupon($discount);

            expect($result)->toBeNull();
        });

        test('createCoupon returns null for fixed discount when Http prevents stray requests', function (): void {
            $discount = Discount::factory()->promoCode()->create([
                'discount_type' => DiscountValueType::Fixed,
                'value' => 1000,
            ]);

            $result = $this->driver->createCoupon($discount);

            expect($result)->toBeNull();
        });
    });

    describe('Subscription methods with Http::preventStrayRequests', function (): void {
        test('startSubscription returns false when Http prevents stray requests', function (): void {
            $user = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $result = $this->driver->startSubscription($order);

            expect($result)->toBeFalse();
        });

        test('startSubscription returns false with all parameters when Http prevents stray requests', function (): void {
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

        test('swapSubscription returns false when Http prevents stray requests', function (): void {
            $user = User::factory()->create();
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create(['external_product_id' => 'prod_123']);
            $product->categories()->attach($category);
            $price = Price::factory()->create([
                'product_id' => $product->id,
                'external_price_id' => 'price_123',
            ]);

            $result = $this->driver->swapSubscription($user, $price);

            expect($result)->toBeFalse();
        });

        test('swapSubscription returns false with all parameters when Http prevents stray requests', function (): void {
            $user = User::factory()->create();
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create(['external_product_id' => 'prod_123']);
            $product->categories()->attach($category);
            $price = Price::factory()->create([
                'product_id' => $product->id,
                'external_price_id' => 'price_123',
            ]);

            $result = $this->driver->swapSubscription(
                user: $user,
                price: $price,
                prorationBehavior: ProrationBehavior::None,
                paymentBehavior: PaymentBehavior::AllowIncomplete,
                options: ['billing_cycle_anchor' => 'now']
            );

            expect($result)->toBeFalse();
        });

        test('cancelSubscription returns false when Http prevents stray requests', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->cancelSubscription($user);

            expect($result)->toBeFalse();
        });

        test('cancelSubscription returns false with cancelNow and reason when Http prevents stray requests', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->cancelSubscription($user, true, 'User requested cancellation');

            expect($result)->toBeFalse();
        });

        test('continueSubscription returns false when Http prevents stray requests', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->continueSubscription($user);

            expect($result)->toBeFalse();
        });

        test('updateSubscription returns null when Http prevents stray requests', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->updateSubscription($user, ['cancel_at_period_end' => false]);

            expect($result)->toBeNull();
        });

        test('currentSubscription returns null when Http prevents stray requests', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->currentSubscription($user);

            expect($result)->toBeNull();
        });

        test('listSubscriptions returns null when Http prevents stray requests', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->listSubscriptions($user);

            expect($result)->toBeNull();
        });

        test('listSubscriptions returns null with filters when Http prevents stray requests', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->listSubscriptions($user, ['active' => true]);

            expect($result)->toBeNull();
        });

        test('listSubscribers returns null when Http prevents stray requests', function (): void {
            $result = $this->driver->listSubscribers();

            expect($result)->toBeNull();
        });

        test('listSubscribers returns null with price filter when Http prevents stray requests', function (): void {
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create(['external_product_id' => 'prod_123']);
            $product->categories()->attach($category);
            $price = Price::factory()->create([
                'product_id' => $product->id,
                'external_price_id' => 'price_123',
            ]);

            $result = $this->driver->listSubscribers($price);

            expect($result)->toBeNull();
        });
    });

    describe('Checkout methods with Http::preventStrayRequests', function (): void {
        test('getCheckoutUrl returns false when Http prevents stray requests', function (): void {
            $user = User::factory()->create(['stripe_id' => 'cus_123']);
            $order = Order::factory()->create(['user_id' => $user->id]);

            $result = $this->driver->getCheckoutUrl($order);

            expect($result)->toBeFalse();
        });

        test('processCheckoutSuccess returns false when Http prevents stray requests', function (): void {
            $user = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'external_checkout_id' => 'cs_123',
            ]);
            $request = Request::create('/checkout/success');

            $result = $this->driver->processCheckoutSuccess($request, $order);

            expect($result)->toBeFalse();
        });

        test('processCheckoutSuccess returns false when order has no external checkout id', function (): void {
            $user = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'external_checkout_id' => null,
            ]);
            $request = Request::create('/checkout/success');

            $result = $this->driver->processCheckoutSuccess($request, $order);

            expect($result)->toBeFalse();
        });

        test('processCheckoutCancel returns false when Http prevents stray requests', function (): void {
            $user = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'external_checkout_id' => 'cs_123',
            ]);
            $request = Request::create('/checkout/cancel');

            $result = $this->driver->processCheckoutCancel($request, $order);

            expect($result)->toBeFalse();
        });

        test('processCheckoutCancel returns false when order has no external checkout id', function (): void {
            $user = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'external_checkout_id' => null,
            ]);
            $request = Request::create('/checkout/cancel');

            $result = $this->driver->processCheckoutCancel($request, $order);

            expect($result)->toBeFalse();
        });
    });

    describe('Order methods with Http::preventStrayRequests', function (): void {
        test('refundOrder returns false when Http prevents stray requests', function (): void {
            $user = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'status' => OrderStatus::Succeeded,
                'external_order_id' => 'pi_123',
            ]);

            $result = $this->driver->refundOrder($order, OrderRefundReason::RequestedByCustomer);

            expect($result)->toBeFalse();
        });

        test('refundOrder returns false with notes when Http prevents stray requests', function (): void {
            $user = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'status' => OrderStatus::Succeeded,
                'external_order_id' => 'pi_123',
            ]);

            $result = $this->driver->refundOrder($order, OrderRefundReason::Duplicate, 'Duplicate order refund');

            expect($result)->toBeFalse();
        });

        test('refundOrder returns false when order has no external order id', function (): void {
            $user = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'status' => OrderStatus::Succeeded,
                'external_order_id' => null,
            ]);

            $result = $this->driver->refundOrder($order, OrderRefundReason::RequestedByCustomer);

            expect($result)->toBeFalse();
        });

        test('refundOrder returns false when order status cannot be refunded', function (): void {
            $user = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'status' => OrderStatus::Pending,
                'external_order_id' => 'pi_123',
            ]);

            $result = $this->driver->refundOrder($order, OrderRefundReason::RequestedByCustomer);

            expect($result)->toBeFalse();
        });

        test('cancelOrder returns false when Http prevents stray requests', function (): void {
            $user = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'status' => OrderStatus::Pending,
                'external_checkout_id' => 'cs_123',
            ]);

            $result = $this->driver->cancelOrder($order);

            expect($result)->toBeFalse();
        });

        test('cancelOrder returns false when order status cannot be cancelled', function (): void {
            $user = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'status' => OrderStatus::Succeeded,
            ]);

            $result = $this->driver->cancelOrder($order);

            expect($result)->toBeFalse();
        });
    });

    describe('Error handling', function (): void {
        test('driver handles missing external product id for delete', function (): void {
            Http::fake();

            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create(['external_product_id' => null]);
            $product->categories()->attach($category);

            $result = $this->driver->deleteProduct($product);

            expect($result)->toBeFalse();
        });

        test('driver handles missing external price id for update', function (): void {
            Http::fake();

            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create(['external_product_id' => 'prod_123']);
            $product->categories()->attach($category);
            $price = Price::factory()->create([
                'product_id' => $product->id,
                'external_price_id' => null,
            ]);

            $result = $this->driver->updatePrice($price);

            expect($result)->toBeNull();
        });

        test('driver handles missing external price id for delete', function (): void {
            Http::fake();

            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create(['external_product_id' => 'prod_123']);
            $product->categories()->attach($category);
            $price = Price::factory()->create([
                'product_id' => $product->id,
                'external_price_id' => null,
            ]);

            $result = $this->driver->deletePrice($price);

            expect($result)->toBeFalse();
        });
    });

    describe('ProrationBehavior enum support', function (): void {
        test('swapSubscription supports CreateProrations behavior', function (): void {
            $user = User::factory()->create();
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create(['external_product_id' => 'prod_123']);
            $product->categories()->attach($category);
            $price = Price::factory()->create([
                'product_id' => $product->id,
                'external_price_id' => 'price_123',
            ]);

            $result = $this->driver->swapSubscription(
                user: $user,
                price: $price,
                prorationBehavior: ProrationBehavior::CreateProrations
            );

            expect($result)->toBeFalse();
        });

        test('swapSubscription supports AlwaysInvoice behavior', function (): void {
            $user = User::factory()->create();
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create(['external_product_id' => 'prod_123']);
            $product->categories()->attach($category);
            $price = Price::factory()->create([
                'product_id' => $product->id,
                'external_price_id' => 'price_123',
            ]);

            $result = $this->driver->swapSubscription(
                user: $user,
                price: $price,
                prorationBehavior: ProrationBehavior::AlwaysInvoice
            );

            expect($result)->toBeFalse();
        });

        test('swapSubscription supports None proration behavior', function (): void {
            $user = User::factory()->create();
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create(['external_product_id' => 'prod_123']);
            $product->categories()->attach($category);
            $price = Price::factory()->create([
                'product_id' => $product->id,
                'external_price_id' => 'price_123',
            ]);

            $result = $this->driver->swapSubscription(
                user: $user,
                price: $price,
                prorationBehavior: ProrationBehavior::None
            );

            expect($result)->toBeFalse();
        });
    });

    describe('PaymentBehavior enum support', function (): void {
        test('swapSubscription supports DefaultIncomplete behavior', function (): void {
            $user = User::factory()->create();
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create(['external_product_id' => 'prod_123']);
            $product->categories()->attach($category);
            $price = Price::factory()->create([
                'product_id' => $product->id,
                'external_price_id' => 'price_123',
            ]);

            $result = $this->driver->swapSubscription(
                user: $user,
                price: $price,
                paymentBehavior: PaymentBehavior::DefaultIncomplete
            );

            expect($result)->toBeFalse();
        });

        test('swapSubscription supports AllowIncomplete behavior', function (): void {
            $user = User::factory()->create();
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create(['external_product_id' => 'prod_123']);
            $product->categories()->attach($category);
            $price = Price::factory()->create([
                'product_id' => $product->id,
                'external_price_id' => 'price_123',
            ]);

            $result = $this->driver->swapSubscription(
                user: $user,
                price: $price,
                paymentBehavior: PaymentBehavior::AllowIncomplete
            );

            expect($result)->toBeFalse();
        });

        test('swapSubscription supports ErrorIfIncomplete behavior', function (): void {
            $user = User::factory()->create();
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create(['external_product_id' => 'prod_123']);
            $product->categories()->attach($category);
            $price = Price::factory()->create([
                'product_id' => $product->id,
                'external_price_id' => 'price_123',
            ]);

            $result = $this->driver->swapSubscription(
                user: $user,
                price: $price,
                paymentBehavior: PaymentBehavior::ErrorIfIncomplete
            );

            expect($result)->toBeFalse();
        });

        test('swapSubscription supports PendingIfIncomplete behavior', function (): void {
            $user = User::factory()->create();
            $category = ProductCategory::factory()->active()->create();
            $product = Product::factory()->create(['external_product_id' => 'prod_123']);
            $product->categories()->attach($category);
            $price = Price::factory()->create([
                'product_id' => $product->id,
                'external_price_id' => 'price_123',
            ]);

            $result = $this->driver->swapSubscription(
                user: $user,
                price: $price,
                paymentBehavior: PaymentBehavior::PendingIfIncomplete
            );

            expect($result)->toBeFalse();
        });
    });

    describe('OrderRefundReason enum support', function (): void {
        test('refundOrder supports Duplicate reason', function (): void {
            $user = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'status' => OrderStatus::Succeeded,
                'external_order_id' => 'pi_123',
            ]);

            $result = $this->driver->refundOrder($order, OrderRefundReason::Duplicate);

            expect($result)->toBeFalse();
        });

        test('refundOrder supports Fraudulent reason', function (): void {
            $user = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'status' => OrderStatus::Succeeded,
                'external_order_id' => 'pi_123',
            ]);

            $result = $this->driver->refundOrder($order, OrderRefundReason::Fraudulent);

            expect($result)->toBeFalse();
        });

        test('refundOrder supports RequestedByCustomer reason', function (): void {
            $user = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'status' => OrderStatus::Succeeded,
                'external_order_id' => 'pi_123',
            ]);

            $result = $this->driver->refundOrder($order, OrderRefundReason::RequestedByCustomer);

            expect($result)->toBeFalse();
        });

        test('refundOrder supports Other reason', function (): void {
            $user = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'status' => OrderStatus::Succeeded,
                'external_order_id' => 'pi_123',
            ]);

            $result = $this->driver->refundOrder($order, OrderRefundReason::Other);

            expect($result)->toBeFalse();
        });
    });
});
