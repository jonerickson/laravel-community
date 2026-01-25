<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;

beforeEach(function (): void {
    $this->appUrl = config('app.url');
});

test('apply valid promo code to order', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()->product()->approved()->visible()->active()->create([
        'allow_discount_codes' => true,
    ]);
    $product->categories()->attach($category);
    $price = Price::factory()->active()->default()->for($product)->create([
        'is_visible' => true,
        'amount' => 10000,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'price_id' => $price->id,
        'quantity' => 1,
    ]);

    $discount = Discount::factory()->promoCode(25)->active()->create([
        'code' => 'TESTCODE25',
        'min_order_amount' => null,
    ]);

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->withSession(['pending_order_id' => $order->id])
        ->postJson('/api/discount/validate', [
            'code' => 'TESTCODE25',
            'order_total' => 10000,
        ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'The discount code was applied successfully.',
    ]);
    $response->assertJsonPath('data.code', 'TESTCODE25');
    $response->assertJsonPath('data.type', 'promo_code');
    $response->assertJsonPath('data.discount_type', 'percentage');
    $response->assertJsonPath('data.discount_value', '25%');

    $this->assertDatabaseHas('orders_discounts', [
        'order_id' => $order->id,
        'discount_id' => $discount->id,
    ]);
});

test('apply valid gift card to order', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()->product()->approved()->visible()->active()->create([
        'allow_discount_codes' => true,
    ]);
    $product->categories()->attach($category);
    $price = Price::factory()->active()->default()->for($product)->create([
        'is_visible' => true,
        'amount' => 10000,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'price_id' => $price->id,
        'quantity' => 1,
    ]);

    $discount = Discount::factory()->giftCard(5000)->active()->create([
        'code' => 'GIFTCARD50',
        'min_order_amount' => null,
        'user_id' => null,
    ]);

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->withSession(['pending_order_id' => $order->id])
        ->postJson('/api/discount/validate', [
            'code' => 'GIFTCARD50',
            'order_total' => 10000,
        ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'The discount code was applied successfully.',
    ]);
    $response->assertJsonPath('data.code', 'GIFTCARD50');
    $response->assertJsonPath('data.type', 'gift_card');
    $response->assertJsonPath('data.discount_type', 'fixed');
});

test('apply discount code is case insensitive', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()->product()->approved()->visible()->active()->create([
        'allow_discount_codes' => true,
    ]);
    $product->categories()->attach($category);
    $price = Price::factory()->active()->default()->for($product)->create([
        'is_visible' => true,
        'amount' => 10000,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'price_id' => $price->id,
        'quantity' => 1,
    ]);

    Discount::factory()->promoCode(10)->active()->create([
        'code' => 'UPPERCASE10',
        'min_order_amount' => null,
    ]);

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->withSession(['pending_order_id' => $order->id])
        ->postJson('/api/discount/validate', [
            'code' => 'uppercase10',
            'order_total' => 10000,
        ]);

    $response->assertOk();
    $response->assertJsonPath('data.code', 'UPPERCASE10');
});

test('apply invalid discount code fails', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->withSession(['pending_order_id' => $order->id])
        ->postJson('/api/discount/validate', [
            'code' => 'INVALIDCODE',
            'order_total' => 10000,
        ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'Invalid or expired discount code.',
    ]);
    $response->assertJsonPath('errors.code.0', 'The discount code is either invalid, has inadequate funds or has expired.');
});

test('apply expired discount code fails', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    Discount::factory()->promoCode(20)->expired()->create([
        'code' => 'EXPIREDCODE',
    ]);

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->withSession(['pending_order_id' => $order->id])
        ->postJson('/api/discount/validate', [
            'code' => 'EXPIREDCODE',
            'order_total' => 10000,
        ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'Invalid or expired discount code.',
    ]);
});

test('apply discount code with depleted balance fails', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    Discount::factory()->giftCard(5000)->depleted()->create([
        'code' => 'DEPLETEDCARD',
    ]);

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->withSession(['pending_order_id' => $order->id])
        ->postJson('/api/discount/validate', [
            'code' => 'DEPLETEDCARD',
            'order_total' => 10000,
        ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'Invalid or expired discount code.',
    ]);
});

test('apply discount code with max uses exceeded fails', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    Discount::factory()->promoCode(15)->create([
        'code' => 'MAXEDOUT',
        'max_uses' => 5,
        'times_used' => 5,
        'expires_at' => null,
    ]);

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->withSession(['pending_order_id' => $order->id])
        ->postJson('/api/discount/validate', [
            'code' => 'MAXEDOUT',
            'order_total' => 10000,
        ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'Invalid or expired discount code.',
    ]);
});

test('apply discount code already applied to order fails', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()->product()->approved()->visible()->active()->create([
        'allow_discount_codes' => true,
    ]);
    $product->categories()->attach($category);
    $price = Price::factory()->active()->default()->for($product)->create([
        'is_visible' => true,
        'amount' => 10000,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'price_id' => $price->id,
        'quantity' => 1,
    ]);

    $discount = Discount::factory()->promoCode(20)->active()->create([
        'code' => 'DUPLICATE',
        'min_order_amount' => null,
    ]);

    $order->discounts()->attach($discount->id, [
        'amount_applied' => 2000,
    ]);

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->withSession(['pending_order_id' => $order->id])
        ->postJson('/api/discount/validate', [
            'code' => 'DUPLICATE',
            'order_total' => 10000,
        ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'This discount has already been applied to your order.',
    ]);
});

test('apply discount code that cannot be used at checkout fails', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()->product()->approved()->visible()->active()->create([
        'allow_discount_codes' => true,
    ]);
    $product->categories()->attach($category);
    $price = Price::factory()->active()->default()->for($product)->create([
        'is_visible' => true,
        'amount' => 10000,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'price_id' => $price->id,
        'quantity' => 1,
    ]);

    Discount::factory()->manual(1000)->create([
        'code' => 'MANUALCODE',
        'expires_at' => null,
    ]);

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->withSession(['pending_order_id' => $order->id])
        ->postJson('/api/discount/validate', [
            'code' => 'MANUALCODE',
            'order_total' => 10000,
        ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'The discount provided cannot be used at checkout. Please provide another discount.',
    ]);
});

test('apply discount code to product that does not allow discounts fails', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()->product()->approved()->visible()->active()->create([
        'name' => 'No Discount Product',
        'allow_discount_codes' => false,
    ]);
    $product->categories()->attach($category);
    $price = Price::factory()->active()->default()->for($product)->create([
        'is_visible' => true,
        'amount' => 10000,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'price_id' => $price->id,
        'quantity' => 1,
    ]);

    Discount::factory()->promoCode(20)->active()->create([
        'code' => 'NODISCOUNTALLOWED',
        'min_order_amount' => null,
    ]);

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->withSession(['pending_order_id' => $order->id])
        ->postJson('/api/discount/validate', [
            'code' => 'NODISCOUNTALLOWED',
            'order_total' => 10000,
        ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
    ]);
    expect($response->json('message'))->toContain('does not allow the use of a discount code');
});

test('apply discount code that belongs to another user fails', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()->product()->approved()->visible()->active()->create([
        'allow_discount_codes' => true,
    ]);
    $product->categories()->attach($category);
    $price = Price::factory()->active()->default()->for($product)->create([
        'is_visible' => true,
        'amount' => 10000,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'price_id' => $price->id,
        'quantity' => 1,
    ]);

    Discount::factory()->promoCode(20)->active()->create([
        'code' => 'OTHERUSERCODE',
        'user_id' => $otherUser->id,
        'min_order_amount' => null,
    ]);

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->withSession(['pending_order_id' => $order->id])
        ->postJson('/api/discount/validate', [
            'code' => 'OTHERUSERCODE',
            'order_total' => 10000,
        ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'The discount you provided belongs to someone else. Please make sure to use a discount code assigned to your account.',
    ]);
});

test('apply discount code below minimum order amount fails', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()->product()->approved()->visible()->active()->create([
        'allow_discount_codes' => true,
    ]);
    $product->categories()->attach($category);
    $price = Price::factory()->active()->default()->for($product)->create([
        'is_visible' => true,
        'amount' => 5000,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'price_id' => $price->id,
        'quantity' => 1,
    ]);

    Discount::factory()->promoCode(20)->active()->create([
        'code' => 'MINORDER100',
        'min_order_amount' => 10000,
    ]);

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->withSession(['pending_order_id' => $order->id])
        ->postJson('/api/discount/validate', [
            'code' => 'MINORDER100',
            'order_total' => 5000,
        ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
    ]);
    expect($response->json('message'))->toContain('must be at least');
});

test('apply discount code validation requires code', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->postJson('/api/discount/validate', [
            'order_total' => 10000,
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['code']);
});

test('apply discount code validation requires order total', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->postJson('/api/discount/validate', [
            'code' => 'SOMECODE',
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['order_total']);
});

test('remove discount from order successfully', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $discount = Discount::factory()->promoCode(20)->active()->create([
        'min_order_amount' => null,
    ]);

    $order->discounts()->attach($discount->id, [
        'amount_applied' => 2000,
    ]);

    $this->assertDatabaseHas('orders_discounts', [
        'order_id' => $order->id,
        'discount_id' => $discount->id,
    ]);

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->withSession(['pending_order_id' => $order->id])
        ->postJson('/api/discount/remove', [
            'discount_id' => $discount->id,
        ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'The discount was removed successfully.',
    ]);

    $this->assertDatabaseMissing('orders_discounts', [
        'order_id' => $order->id,
        'discount_id' => $discount->id,
    ]);
});

test('remove discount validation requires discount id', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->postJson('/api/discount/remove', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['discount_id']);
});

test('remove discount with non-existent discount id fails validation', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->postJson('/api/discount/remove', [
            'discount_id' => 99999,
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['discount_id']);
    $response->assertJsonPath('errors.discount_id.0', 'The specified discount does not exist.');
});

test('guest can apply discount code', function (): void {
    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()->product()->approved()->visible()->active()->create([
        'allow_discount_codes' => true,
    ]);
    $product->categories()->attach($category);
    $price = Price::factory()->active()->default()->for($product)->create([
        'is_visible' => true,
        'amount' => 10000,
    ]);

    $discount = Discount::factory()->promoCode(25)->active()->create([
        'code' => 'GUESTCODE',
        'min_order_amount' => null,
    ]);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/discount/validate', [
            'code' => 'GUESTCODE',
            'order_total' => 10000,
        ]);

    $response->assertStatus(400);
    $response->assertJson([
        'success' => false,
        'message' => 'Unable to create order.',
    ]);
});
