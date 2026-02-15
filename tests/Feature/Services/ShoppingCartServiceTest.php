<?php

declare(strict_types=1);

use App\Data\CartData;
use App\Data\CartItemData;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Enums\OrderStatus;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\DiscountService;
use App\Services\ShoppingCartService;
use Illuminate\Http\Request;
use Illuminate\Session\Store;

function createShoppingCartService(?User $user = null, ?int $pendingOrderId = null): ShoppingCartService
{
    $request = Request::create('/', 'GET');

    // Set up session on the request using Laravel's session handler
    $sessionHandler = new Illuminate\Session\ArraySessionHandler(120);
    $sessionStore = new Store('test', $sessionHandler);
    $sessionStore->start();

    if ($pendingOrderId !== null) {
        $sessionStore->put('pending_order_id', $pendingOrderId);
    }

    $request->setLaravelSession($sessionStore);

    return new ShoppingCartService(
        request: $request,
        discountService: app(DiscountService::class),
        user: $user,
    );
}

function createProductWithPrice(float $priceAmount): array
{
    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()->product()->approved()->visible()->active()->create([
        'allow_discount_codes' => true,
    ]);
    $product->categories()->attach($category);

    $price = Price::factory()->active()->default()->for($product)->create([
        'is_visible' => true,
        'amount' => $priceAmount,
    ]);

    return [$product, $price, $category];
}

describe('getCart', function (): void {
    test('returns empty cart for guest user', function (): void {
        $service = createShoppingCartService();

        $cart = $service->getCart();

        expect($cart)
            ->toBeInstanceOf(CartData::class)
            ->cartCount->toBe(0)
            ->cartItems->toBeEmpty();
    });

    test('returns empty cart when no pending order exists', function (): void {
        $user = User::factory()->create();
        $service = createShoppingCartService($user);

        // Service will create a new order when none exists
        $cart = $service->getCart();

        // Since no items were added, cart is empty
        expect($cart)
            ->toBeInstanceOf(CartData::class)
            ->cartCount->toBe(0);
    });

    test('returns cart with items when pending order exists', function (): void {
        $user = User::factory()->create();
        [$product, $price] = createProductWithPrice(25.00);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'price_id' => $price->id,
            'quantity' => 2,
            'amount' => 25.00,
        ]);

        $service = createShoppingCartService($user, $order->id);
        $cart = $service->getCart();

        expect($cart)
            ->toBeInstanceOf(CartData::class)
            ->cartCount->toBe(1);
        expect($cart->cartItems[0])
            ->toBeInstanceOf(CartItemData::class)
            ->priceId->toBe($price->id)
            ->quantity->toBe(2);
    });

    test('cart items are sorted by name', function (): void {
        $user = User::factory()->create();

        $category = ProductCategory::factory()->active()->visible()->create();

        // Create products with specific names for sorting test
        $productZ = Product::factory()->product()->approved()->visible()->active()->create(['name' => 'Zebra Product']);
        $productA = Product::factory()->product()->approved()->visible()->active()->create(['name' => 'Apple Product']);
        $productM = Product::factory()->product()->approved()->visible()->active()->create(['name' => 'Mango Product']);

        $productZ->categories()->attach($category);
        $productA->categories()->attach($category);
        $productM->categories()->attach($category);

        $priceZ = Price::factory()->active()->default()->for($productZ)->create(['amount' => 10.00, 'is_visible' => true]);
        $priceA = Price::factory()->active()->default()->for($productA)->create(['amount' => 15.00, 'is_visible' => true]);
        $priceM = Price::factory()->active()->default()->for($productM)->create(['amount' => 20.00, 'is_visible' => true]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
        ]);

        // Add items in non-alphabetical order
        OrderItem::create(['order_id' => $order->id, 'price_id' => $priceZ->id, 'quantity' => 1, 'amount' => 10.00]);
        OrderItem::create(['order_id' => $order->id, 'price_id' => $priceA->id, 'quantity' => 1, 'amount' => 15.00]);
        OrderItem::create(['order_id' => $order->id, 'price_id' => $priceM->id, 'quantity' => 1, 'amount' => 20.00]);

        $service = createShoppingCartService($user, $order->id);
        $cart = $service->getCart();

        expect($cart->cartCount)->toBe(3);
        expect($cart->cartItems[0]->name)->toBe('Apple Product');
        expect($cart->cartItems[1]->name)->toBe('Mango Product');
        expect($cart->cartItems[2]->name)->toBe('Zebra Product');
    });
});

describe('getCartCount', function (): void {
    test('returns zero for guest user', function (): void {
        $service = createShoppingCartService();

        $count = $service->getCartCount();

        expect($count)->toBe(0);
    });

    test('returns count of items in pending order', function (): void {
        $user = User::factory()->create();
        [$product1, $price1] = createProductWithPrice(10.00);
        [$product2, $price2] = createProductWithPrice(20.00);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
        ]);

        OrderItem::create(['order_id' => $order->id, 'price_id' => $price1->id, 'quantity' => 1, 'amount' => 10.00]);
        OrderItem::create(['order_id' => $order->id, 'price_id' => $price2->id, 'quantity' => 2, 'amount' => 20.00]);

        $service = createShoppingCartService($user, $order->id);
        $count = $service->getCartCount();

        expect($count)->toBe(2); // 2 line items, not 3 total quantity
    });
});

describe('getOrCreatePendingOrder', function (): void {
    test('returns null for guest user', function (): void {
        $service = createShoppingCartService();

        $order = $service->getOrCreatePendingOrder();

        expect($order)->toBeNull();
    });

    test('creates new order when none exists in session', function (): void {
        $user = User::factory()->create();
        $service = createShoppingCartService($user);

        $order = $service->getOrCreatePendingOrder();

        expect($order)
            ->toBeInstanceOf(Order::class)
            ->user_id->toBe($user->id)
            ->status->toBe(OrderStatus::Pending);
    });

    test('returns existing order from session', function (): void {
        $user = User::factory()->create();
        $existingOrder = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
        ]);

        $service = createShoppingCartService($user, $existingOrder->id);
        $order = $service->getOrCreatePendingOrder();

        expect($order->id)->toBe($existingOrder->id);
    });

    test('creates new order when session order belongs to different user', function (): void {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherOrder = Order::factory()->create([
            'user_id' => $otherUser->id,
            'status' => OrderStatus::Pending,
        ]);

        $service = createShoppingCartService($user, $otherOrder->id);
        $order = $service->getOrCreatePendingOrder();

        expect($order->id)->not->toBe($otherOrder->id);
        expect($order->user_id)->toBe($user->id);
    });

    test('creates new order when session order is not pending', function (): void {
        $user = User::factory()->create();

        $completedOrder = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Succeeded,
        ]);

        $service = createShoppingCartService($user, $completedOrder->id);
        $order = $service->getOrCreatePendingOrder();

        expect($order->id)->not->toBe($completedOrder->id);
        expect($order->status)->toBe(OrderStatus::Pending);
    });
});

describe('clearCart and clearPendingOrder', function (): void {
    test('clearCart removes pending order from database', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
        ]);

        $service = createShoppingCartService($user, $order->id);
        $service->clearCart();

        expect(Order::find($order->id))->toBeNull();
    });

    test('clearCart does nothing for guest user', function (): void {
        $service = createShoppingCartService();

        // Should not throw exception
        $service->clearCart();

        expect(true)->toBeTrue();
    });

    test('clearCart does not remove non-pending orders', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Succeeded,
        ]);

        $service = createShoppingCartService($user, $order->id);
        $service->clearCart();

        expect(Order::find($order->id))->not->toBeNull();
    });

    test('clearCart does not remove other users orders', function (): void {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherOrder = Order::factory()->create([
            'user_id' => $otherUser->id,
            'status' => OrderStatus::Pending,
        ]);

        $service = createShoppingCartService($user, $otherOrder->id);
        $service->clearCart();

        expect(Order::find($otherOrder->id))->not->toBeNull();
    });
});

describe('addItem', function (): void {
    test('adds item to cart for authenticated user', function (): void {
        $user = User::factory()->create();
        [$product, $price] = createProductWithPrice(50.00);

        $service = createShoppingCartService($user);
        $cart = $service->addItem($price->id, 3);

        expect($cart)
            ->toBeInstanceOf(CartData::class)
            ->cartCount->toBe(1);
        expect($cart->cartItems[0])
            ->priceId->toBe($price->id)
            ->quantity->toBe(3);
    });

    test('returns empty cart for guest user', function (): void {
        [$product, $price] = createProductWithPrice(50.00);

        $service = createShoppingCartService();
        $cart = $service->addItem($price->id, 2);

        expect($cart)
            ->cartCount->toBe(0)
            ->cartItems->toBeEmpty();
    });

    test('adds multiple different items to cart', function (): void {
        $user = User::factory()->create();
        [$product1, $price1] = createProductWithPrice(25.00);
        [$product2, $price2] = createProductWithPrice(50.00);

        $service = createShoppingCartService($user);
        $service->addItem($price1->id, 1);

        $cart = $service->addItem($price2->id, 2);

        expect($cart->cartCount)->toBe(2);
    });
});

describe('updateItem', function (): void {
    test('updates quantity of existing item', function (): void {
        $user = User::factory()->create();
        [$product, $price] = createProductWithPrice(30.00);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
        ]);
        OrderItem::create(['order_id' => $order->id, 'price_id' => $price->id, 'quantity' => 1, 'amount' => 30.00]);

        $service = createShoppingCartService($user, $order->id);
        $cart = $service->updateItem($price->id, 5);

        expect($cart->cartCount)->toBe(1);
        expect($cart->cartItems[0]->quantity)->toBe(5);
    });

    test('removes other items from cart when updating', function (): void {
        $user = User::factory()->create();
        [$product1, $price1] = createProductWithPrice(20.00);
        [$product2, $price2] = createProductWithPrice(30.00);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
        ]);
        OrderItem::create(['order_id' => $order->id, 'price_id' => $price1->id, 'quantity' => 1, 'amount' => 20.00]);
        OrderItem::create(['order_id' => $order->id, 'price_id' => $price2->id, 'quantity' => 2, 'amount' => 30.00]);

        $service = createShoppingCartService($user, $order->id);
        $cart = $service->updateItem($price1->id, 3);

        // Update removes all OTHER items, keeps only the specified one
        expect($cart->cartCount)->toBe(1);
        expect($cart->cartItems[0]->priceId)->toBe($price1->id);
        expect($cart->cartItems[0]->quantity)->toBe(3);
    });

    test('creates item if it does not exist', function (): void {
        $user = User::factory()->create();
        [$product, $price] = createProductWithPrice(40.00);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
        ]);

        $service = createShoppingCartService($user, $order->id);
        $cart = $service->updateItem($price->id, 4);

        expect($cart->cartCount)->toBe(1);
        expect($cart->cartItems[0]->priceId)->toBe($price->id);
        expect($cart->cartItems[0]->quantity)->toBe(4);
    });

    test('returns empty cart for guest user', function (): void {
        [$product, $price] = createProductWithPrice(40.00);

        $service = createShoppingCartService();
        $cart = $service->updateItem($price->id, 2);

        expect($cart->cartCount)->toBe(0);
    });
});

describe('removeItem', function (): void {
    test('removes item from cart', function (): void {
        $user = User::factory()->create();
        [$product, $price] = createProductWithPrice(35.00);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
        ]);
        OrderItem::create(['order_id' => $order->id, 'price_id' => $price->id, 'quantity' => 2, 'amount' => 35.00]);

        $service = createShoppingCartService($user, $order->id);
        $cart = $service->removeItem($price->id);

        expect($cart->cartCount)->toBe(0);
        expect($cart->cartItems)->toBeEmpty();
    });

    test('removes only specified item from cart', function (): void {
        $user = User::factory()->create();
        [$product1, $price1] = createProductWithPrice(25.00);
        [$product2, $price2] = createProductWithPrice(35.00);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
        ]);
        OrderItem::create(['order_id' => $order->id, 'price_id' => $price1->id, 'quantity' => 1, 'amount' => 25.00]);
        OrderItem::create(['order_id' => $order->id, 'price_id' => $price2->id, 'quantity' => 2, 'amount' => 35.00]);

        $service = createShoppingCartService($user, $order->id);
        $cart = $service->removeItem($price1->id);

        expect($cart->cartCount)->toBe(1);
        expect($cart->cartItems[0]->priceId)->toBe($price2->id);
    });

    test('returns empty cart for guest user', function (): void {
        [$product, $price] = createProductWithPrice(35.00);

        $service = createShoppingCartService();
        $cart = $service->removeItem($price->id);

        expect($cart->cartCount)->toBe(0);
    });

    test('does nothing when item does not exist in cart', function (): void {
        $user = User::factory()->create();
        [$product1, $price1] = createProductWithPrice(25.00);
        [$product2, $price2] = createProductWithPrice(35.00);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
        ]);
        OrderItem::create(['order_id' => $order->id, 'price_id' => $price1->id, 'quantity' => 1, 'amount' => 25.00]);

        $service = createShoppingCartService($user, $order->id);
        $cart = $service->removeItem($price2->id);

        expect($cart->cartCount)->toBe(1);
        expect($cart->cartItems[0]->priceId)->toBe($price1->id);
    });
});

describe('applyDiscount', function (): void {
    test('applies valid discount to order', function (): void {
        $user = User::factory()->create();
        [$product, $price] = createProductWithPrice(100.00);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
        ]);
        OrderItem::create(['order_id' => $order->id, 'price_id' => $price->id, 'quantity' => 1, 'amount' => 100.00]);

        $discount = Discount::factory()->promoCode(20)->active()->create([
            'min_order_amount' => null,
        ]);

        $service = createShoppingCartService($user, $order->id);
        $service->applyDiscount($order, $discount);

        $order->refresh();
        $order->load('discounts');

        expect($order->discounts)->toHaveCount(1);
        expect($order->discounts->first()->id)->toBe($discount->id);
    });

    test('throws exception when discount already applied', function (): void {
        $user = User::factory()->create();
        [$product, $price] = createProductWithPrice(100.00);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
        ]);
        OrderItem::create(['order_id' => $order->id, 'price_id' => $price->id, 'quantity' => 1, 'amount' => 100.00]);

        $discount = Discount::factory()->promoCode(20)->active()->create([
            'min_order_amount' => null,
        ]);

        // Apply discount first
        $order->discounts()->attach($discount->id, ['amount_applied' => 20.00]);

        $service = createShoppingCartService($user);

        expect(fn () => $service->applyDiscount($order, $discount))
            ->toThrow(RuntimeException::class, 'This discount has already been applied to your order.');
    });

    test('throws exception when discount cannot be used at checkout', function (): void {
        $user = User::factory()->create();
        [$product, $price] = createProductWithPrice(100.00);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
        ]);
        OrderItem::create(['order_id' => $order->id, 'price_id' => $price->id, 'quantity' => 1, 'amount' => 100.00]);

        // Manual discounts cannot be used at checkout
        $discount = Discount::factory()->create([
            'type' => DiscountType::Manual,
            'discount_type' => DiscountValueType::Percentage,
            'value' => 20,
            'expires_at' => now()->addMonth(),
        ]);

        $service = createShoppingCartService($user);

        expect(fn () => $service->applyDiscount($order, $discount))
            ->toThrow(RuntimeException::class, 'The discount provided cannot be used at checkout.');
    });

    test('throws exception when product disallows discount codes', function (): void {
        $user = User::factory()->create();

        $category = ProductCategory::factory()->active()->visible()->create();
        $product = Product::factory()->product()->approved()->visible()->active()->create([
            'allow_discount_codes' => false,
        ]);
        $product->categories()->attach($category);
        $price = Price::factory()->active()->default()->for($product)->create([
            'is_visible' => true,
            'amount' => 100.00,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
        ]);
        OrderItem::create(['order_id' => $order->id, 'price_id' => $price->id, 'quantity' => 1, 'amount' => 100.00]);

        $discount = Discount::factory()->promoCode(20)->active()->create([
            'min_order_amount' => null,
        ]);

        $service = createShoppingCartService($user);

        expect(fn () => $service->applyDiscount($order, $discount))
            ->toThrow(RuntimeException::class, 'does not allow the use of a discount code');
    });

    test('throws exception when discount belongs to different user', function (): void {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        [$product, $price] = createProductWithPrice(100.00);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
        ]);
        OrderItem::create(['order_id' => $order->id, 'price_id' => $price->id, 'quantity' => 1, 'amount' => 100.00]);

        // Discount assigned to different user
        $discount = Discount::factory()->promoCode(20)->active()->create([
            'min_order_amount' => null,
            'user_id' => $otherUser->id,
        ]);

        $service = createShoppingCartService($user);

        expect(fn () => $service->applyDiscount($order, $discount))
            ->toThrow(RuntimeException::class, 'The discount you provided belongs to someone else.');
    });

    test('throws exception when order below minimum amount', function (): void {
        $user = User::factory()->create();
        [$product, $price] = createProductWithPrice(25.00);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
        ]);
        OrderItem::create(['order_id' => $order->id, 'price_id' => $price->id, 'quantity' => 1, 'amount' => 25.00]);

        // Discount requires minimum $50
        $discount = Discount::factory()->promoCode(20)->active()->create([
            'min_order_amount' => 50,
        ]);

        $service = createShoppingCartService($user);

        expect(fn () => $service->applyDiscount($order, $discount))
            ->toThrow(RuntimeException::class, 'The order subtotal must be at least');
    });
});

describe('removeDiscount', function (): void {
    test('removes discount from order', function (): void {
        $user = User::factory()->create();
        [$product, $price] = createProductWithPrice(100.00);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
        ]);
        OrderItem::create(['order_id' => $order->id, 'price_id' => $price->id, 'quantity' => 1, 'amount' => 100.00]);

        $discount = Discount::factory()->promoCode(20)->active()->create([
            'min_order_amount' => null,
        ]);
        $order->discounts()->attach($discount->id, ['amount_applied' => 20.00]);

        $service = createShoppingCartService($user);
        $service->removeDiscount($order, $discount->id);

        $order->refresh();
        $order->load('discounts');

        expect($order->discounts)->toHaveCount(0);
    });

    test('removes only specified discount from order', function (): void {
        $user = User::factory()->create();
        [$product, $price] = createProductWithPrice(100.00);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
        ]);
        OrderItem::create(['order_id' => $order->id, 'price_id' => $price->id, 'quantity' => 1, 'amount' => 100.00]);

        $discount1 = Discount::factory()->promoCode(10)->active()->create([
            'code' => 'DISCOUNT1',
            'min_order_amount' => null,
        ]);
        $discount2 = Discount::factory()->promoCode(15)->active()->create([
            'code' => 'DISCOUNT2',
            'min_order_amount' => null,
        ]);

        $order->discounts()->attach($discount1->id, ['amount_applied' => 10.00]);
        $order->discounts()->attach($discount2->id, ['amount_applied' => 15.00]);

        $service = createShoppingCartService($user);
        $service->removeDiscount($order, $discount1->id);

        $order->refresh();
        $order->load('discounts');

        expect($order->discounts)->toHaveCount(1);
        expect($order->discounts->first()->id)->toBe($discount2->id);
    });
});

describe('calculate totals', function (): void {
    test('order subtotal reflects cart item amounts', function (): void {
        $user = User::factory()->create();
        [$product1, $price1] = createProductWithPrice(25.00);
        [$product2, $price2] = createProductWithPrice(50.00);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
        ]);
        OrderItem::create(['order_id' => $order->id, 'price_id' => $price1->id, 'quantity' => 2, 'amount' => 25.00]);
        OrderItem::create(['order_id' => $order->id, 'price_id' => $price2->id, 'quantity' => 1, 'amount' => 50.00]);

        $order->load('items');

        // Order subtotal is sum of (item amount * quantity)
        // Note: Order model has amount_subtotal accessor
        expect($order->amount_subtotal)->toBe(100.00); // (25*2) + (50*1) = 100
    });

    test('cart includes product available prices', function (): void {
        $user = User::factory()->create();

        $category = ProductCategory::factory()->active()->visible()->create();
        $product = Product::factory()->product()->approved()->visible()->active()->create();
        $product->categories()->attach($category);

        // Create multiple prices for the product
        $defaultPrice = Price::factory()->active()->default()->for($product)->create([
            'name' => 'Standard',
            'is_visible' => true,
            'amount' => 30.00,
        ]);
        Price::factory()->active()->for($product)->create([
            'name' => 'Premium',
            'is_visible' => true,
            'amount' => 50.00,
        ]);
        Price::factory()->inactive()->for($product)->create([
            'name' => 'Hidden',
            'is_visible' => true,
            'amount' => 100.00,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
        ]);
        OrderItem::create(['order_id' => $order->id, 'price_id' => $defaultPrice->id, 'quantity' => 1, 'amount' => 30.00]);

        $service = createShoppingCartService($user, $order->id);
        $cart = $service->getCart();

        // Available prices only includes active and visible prices
        expect(count($cart->cartItems[0]->availablePrices))->toBe(2);
    });
});
