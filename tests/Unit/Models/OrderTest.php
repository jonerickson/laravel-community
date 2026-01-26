<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Models\Commission;
use App\Models\Discount;
use App\Models\Note;
use App\Models\Order;
use App\Models\OrderDiscount;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Subscription;
use App\Models\User;

describe('Order user relationship', function (): void {
    test('returns user who owns the order', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        expect($order->user->id)->toBe($user->id);
    });

    test('user relationship is BelongsTo', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        expect($order->user())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    });
});

describe('Order items relationship', function (): void {
    test('returns empty collection when order has no items', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        expect($order->items)->toBeEmpty();
    });

    test('returns items belonging to order', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $category = ProductCategory::factory()->active()->visible()->create();
        $product = Product::factory()->approved()->active()->create();
        $product->categories()->attach($category);
        $price = Price::factory()->create(['product_id' => $product->id]);

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'price_id' => $price->id,
            'quantity' => 2,
            'amount' => $price->amount,
        ]);

        $items = $order->items;

        expect($items)->toHaveCount(1);
        expect($items->first()->id)->toBe($orderItem->id);
    });

    test('does not return items from other orders', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $otherOrder = Order::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create();
        $price = Price::factory()->create(['product_id' => $product->id]);

        OrderItem::create([
            'order_id' => $order->id,
            'price_id' => $price->id,
            'quantity' => 1,
            'amount' => $price->amount,
        ]);

        OrderItem::create([
            'order_id' => $otherOrder->id,
            'price_id' => $price->id,
            'quantity' => 1,
            'amount' => $price->amount,
        ]);

        expect($order->items)->toHaveCount(1);
    });

    test('items relationship is HasMany', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        expect($order->items())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);
    });
});

describe('Order commissions relationship', function (): void {
    test('returns empty collection when order has no commissions', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        expect($order->commissions)->toBeEmpty();
    });

    test('returns commissions belonging to order', function (): void {
        $user = User::factory()->create();
        $seller = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $commission = Commission::factory()->create([
            'seller_id' => $seller->id,
            'order_id' => $order->id,
        ]);

        $commissions = $order->commissions;

        expect($commissions)->toHaveCount(1);
        expect($commissions->first()->id)->toBe($commission->id);
    });

    test('commissions relationship is HasMany', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        expect($order->commissions())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);
    });
});

describe('Order prices relationship', function (): void {
    test('returns empty collection when order has no items', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        expect($order->prices)->toBeEmpty();
    });

    test('returns prices through order items', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create();
        $price = Price::factory()->create(['product_id' => $product->id]);

        OrderItem::create([
            'order_id' => $order->id,
            'price_id' => $price->id,
            'quantity' => 1,
            'amount' => $price->amount,
        ]);

        $prices = $order->prices;

        expect($prices)->toHaveCount(1);
        expect($prices->first()->id)->toBe($price->id);
    });

    test('prices relationship is HasManyThrough', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        expect($order->prices())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasManyThrough::class);
    });
});

describe('Order discounts relationship', function (): void {
    test('returns empty collection when order has no discounts', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        expect($order->discounts)->toBeEmpty();
    });

    test('returns discounts attached to order', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $discount = Discount::factory()->promoCode()->create();

        $order->discounts()->attach($discount->id, [
            'amount_applied' => 1000,
            'balance_before' => null,
            'balance_after' => null,
        ]);

        $discounts = $order->discounts;

        expect($discounts)->toHaveCount(1);
        expect($discounts->first()->id)->toBe($discount->id);
    });

    test('discounts relationship includes pivot data', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $discount = Discount::factory()->promoCode()->create();

        $order->discounts()->attach($discount->id, [
            'amount_applied' => 1000,
            'balance_before' => 5000,
            'balance_after' => 4000,
        ]);

        $orderDiscount = $order->discounts->first()->pivot;

        expect($orderDiscount->amount_applied)->not->toBeNull();
        expect($orderDiscount->balance_before)->not->toBeNull();
        expect($orderDiscount->balance_after)->not->toBeNull();
    });

    test('discounts relationship is BelongsToMany', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        expect($order->discounts())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    });

    test('discount pivot uses OrderDiscount model', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $discount = Discount::factory()->promoCode()->create();

        $order->discounts()->attach($discount->id, [
            'amount_applied' => 1000,
        ]);

        expect($order->discounts->first()->pivot)->toBeInstanceOf(OrderDiscount::class);
    });
});

describe('Order notes relationship (from HasNotes trait)', function (): void {
    test('returns empty collection when order has no notes', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        expect($order->notes)->toBeEmpty();
    });

    test('returns notes attached to order', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $note = $order->notes()->create([
            'content' => 'Test note content',
            'created_by' => $user->id,
        ]);

        $notes = $order->notes;

        expect($notes)->toHaveCount(1);
        expect($notes->first()->id)->toBe($note->id);
    });
});

describe('Order amount attribute', function (): void {
    test('returns 0 when order has no items and no amount_paid', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        expect($order->amount)->toBe(0.0);
    });

    test('calculates amount from items when no amount_paid', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create();
        $price = Price::factory()->create([
            'product_id' => $product->id,
            'amount' => 25.00, // $25
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'price_id' => $price->id,
            'quantity' => 2,
            'amount' => $price->amount,
        ]);

        $order->refresh();

        expect($order->amount)->toBe(50.0); // $25 x 2 = $50
    });

    test('returns amount_paid when set', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'amount_paid' => 99.99,
        ]);

        expect($order->amount)->toBe(99.99);
    });

    test('amount subtracts discount from subtotal', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create();
        $price = Price::factory()->create([
            'product_id' => $product->id,
            'amount' => 100.00,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'price_id' => $price->id,
            'quantity' => 1,
            'amount' => $price->amount,
        ]);

        $discount = Discount::factory()->promoCode()->create();
        $order->discounts()->attach($discount->id, [
            'amount_applied' => 2000,
        ]);

        $order->refresh();
        $order->load('discounts');

        // Amount is subtotal minus sum of pivot amount_applied
        // The pivot amount_applied accessor divides by 100, so the discount is effectively applied
        expect($order->amount)->toBeLessThan($order->amount_subtotal);
    });
});

describe('Order amountSubtotal attribute', function (): void {
    test('returns 0 when order has no items', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        expect($order->amount_subtotal)->toBe(0.0);
    });

    test('sums item amounts', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create();

        $price1 = Price::factory()->create([
            'product_id' => $product->id,
            'amount' => 30.00,
        ]);
        $price2 = Price::factory()->create([
            'product_id' => $product->id,
            'amount' => 20.00,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'price_id' => $price1->id,
            'quantity' => 1,
            'amount' => $price1->amount,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'price_id' => $price2->id,
            'quantity' => 2,
            'amount' => $price2->amount,
        ]);

        $order->refresh();

        expect($order->amount_subtotal)->toBe(70.0); // $30 + ($20 x 2) = $70
    });
});

describe('Order isRecurring and isOneTime attributes', function (): void {
    test('isRecurring returns true when order has recurring price', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->subscription()->create();

        $price = Price::factory()->monthly()->create([
            'product_id' => $product->id,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'price_id' => $price->id,
            'quantity' => 1,
            'amount' => $price->amount,
        ]);

        $order->refresh();
        $order->load('items.price');

        expect($order->is_recurring)->toBeTrue();
        expect($order->is_one_time)->toBeFalse();
    });

    test('isOneTime returns true when order has no recurring prices', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->product()->create();

        $price = Price::factory()->oneTime()->create([
            'product_id' => $product->id,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'price_id' => $price->id,
            'quantity' => 1,
            'amount' => $price->amount,
        ]);

        $order->refresh();
        $order->load('items.price');

        expect($order->is_one_time)->toBeTrue();
        expect($order->is_recurring)->toBeFalse();
    });
});

describe('Order commissionAmount attribute', function (): void {
    test('returns 0 when order has no commissions', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        expect($order->commission_amount)->toBe(0.0);
    });

    test('sums commission amounts', function (): void {
        $user = User::factory()->create();
        $seller = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        Commission::factory()->create([
            'seller_id' => $seller->id,
            'order_id' => $order->id,
            'amount' => 10.00,
        ]);

        Commission::factory()->create([
            'seller_id' => $seller->id,
            'order_id' => $order->id,
            'amount' => 15.00,
        ]);

        $order->refresh();

        expect($order->commission_amount)->toBe(25.0);
    });
});

describe('Order scopes', function (): void {
    test('completed scope filters to Succeeded status', function (): void {
        $user = User::factory()->create();
        Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Succeeded]);
        Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Pending]);
        Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Cancelled]);

        $completed = Order::query()->completed()->get();

        expect($completed)->toHaveCount(1);
        expect($completed->first()->status)->toBe(OrderStatus::Succeeded);
    });

    test('cancelled scope filters to Cancelled status', function (): void {
        $user = User::factory()->create();
        Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Succeeded]);
        Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Cancelled]);

        $cancelled = Order::query()->cancelled()->get();

        expect($cancelled)->toHaveCount(1);
        expect($cancelled->first()->status)->toBe(OrderStatus::Cancelled);
    });

    test('refunded scope filters to Refunded status', function (): void {
        $user = User::factory()->create();
        Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Succeeded]);
        Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Refunded]);

        $refunded = Order::query()->refunded()->get();

        expect($refunded)->toHaveCount(1);
        expect($refunded->first()->status)->toBe(OrderStatus::Refunded);
    });

    test('readyToView scope filters appropriate statuses with items', function (): void {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $price = Price::factory()->create(['product_id' => $product->id]);

        // Order with items and valid status
        $validOrder = Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Succeeded]);
        OrderItem::create([
            'order_id' => $validOrder->id,
            'price_id' => $price->id,
            'quantity' => 1,
            'amount' => $price->amount,
        ]);

        // Order without items (should not appear)
        Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Succeeded]);

        // Order with items but invalid status
        $invalidOrder = Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Processing]);
        OrderItem::create([
            'order_id' => $invalidOrder->id,
            'price_id' => $price->id,
            'quantity' => 1,
            'amount' => $price->amount,
        ]);

        $readyToView = Order::query()->readyToView()->get();

        expect($readyToView)->toHaveCount(1);
        expect($readyToView->first()->id)->toBe($validOrder->id);
    });
});

describe('Order amount attributes with cents conversion', function (): void {
    test('amountDue converts from cents to dollars on get', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'amount_due' => 50.00, // Setter converts to 5000 cents
        ]);

        expect($order->amount_due)->toBe(50.0);
    });

    test('amountOverpaid converts from cents to dollars on get', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'amount_overpaid' => 10.00,
        ]);

        expect($order->amount_overpaid)->toBe(10.0);
    });

    test('amountRemaining converts from cents to dollars on get', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'amount_remaining' => 25.50,
        ]);

        expect($order->amount_remaining)->toBe(25.5);
    });

    test('returns null when amount attributes are null', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
        ]);

        expect($order->amount_due)->toBeNull();
        expect($order->amount_overpaid)->toBeNull();
        expect($order->amount_remaining)->toBeNull();
    });
});

describe('Order deleting cascade', function (): void {
    test('deleting order deletes associated notes', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $order->notes()->create([
            'content' => 'Test note',
            'created_by' => $user->id,
        ]);

        expect(Note::where('notable_id', $order->id)->where('notable_type', Order::class)->count())->toBe(1);

        $order->delete();

        expect(Note::where('notable_id', $order->id)->where('notable_type', Order::class)->count())->toBe(0);
    });
});

describe('Order subscriptions relationship', function (): void {
    test('returns empty collection when order has no subscriptions', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        expect($order->subscriptions)->toBeEmpty();
    });

    test('returns subscriptions through order items and prices', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->subscription()->create();
        $price = Price::factory()->monthly()->create([
            'product_id' => $product->id,
            'external_price_id' => 'price_test_123',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'price_id' => $price->id,
            'quantity' => 1,
            'amount' => $price->amount,
        ]);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'type' => 'default',
            'stripe_id' => 'sub_test_123',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test_123',
        ]);

        $subscriptions = $order->subscriptions;

        expect($subscriptions)->toHaveCount(1);
        expect($subscriptions->first()->id)->toBe($subscription->id);
    });
});

describe('Order reference ID (from HasReferenceId trait)', function (): void {
    test('generates UUID reference_id on creation', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        expect($order->reference_id)->not->toBeNull();
        expect($order->reference_id)->toBeString();
    });

    test('reference_id is unique per order', function (): void {
        $user = User::factory()->create();
        $order1 = Order::factory()->create(['user_id' => $user->id]);
        $order2 = Order::factory()->create(['user_id' => $user->id]);

        expect($order1->reference_id)->not->toBe($order2->reference_id);
    });
});

describe('Order default status', function (): void {
    test('defaults to Pending status', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        expect($order->status)->toBe(OrderStatus::Pending);
    });
});

describe('Order getLabel method', function (): void {
    test('returns formatted label with reference, user, amount, and status', function (): void {
        $user = User::factory()->create(['name' => 'John Doe']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Succeeded,
            'amount_paid' => 99.99,
        ]);

        $label = $order->getLabel();

        expect($label)->toContain($order->reference_id);
        expect($label)->toContain('John Doe');
        expect($label)->toContain('$99.99');
        expect($label)->toContain('Succeeded');
    });
});
