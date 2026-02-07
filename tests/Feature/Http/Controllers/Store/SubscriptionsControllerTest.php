<?php

declare(strict_types=1);

use App\Enums\PolicyConsentContext;
use App\Managers\PaymentManager;
use App\Models\Policy;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Mockery\MockInterface;

test('subscription checkout records policy consents when product has policies', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()->subscription()->approved()->visible()->active()->create();
    $product->categories()->attach($category);
    $policy = Policy::factory()->create(['is_active' => true, 'effective_at' => now()->subDay()]);
    $product->policies()->attach($policy);
    $price = Price::factory()->recurring()->active()->default()->for($product)->create([
        'is_visible' => true,
        'amount' => 1000,
        'external_price_id' => 'price_test123',
    ]);

    $this->mock(PaymentManager::class, function (MockInterface $mock): void {
        $mock->shouldReceive('currentSubscription')->andReturn(null);
        $mock->shouldReceive('startSubscription')->once()->andReturn('https://checkout.stripe.com/pay/cs_test');
    });

    $this->post(route('store.subscriptions.store'), [
        'price_id' => $price->id,
    ]);

    $this->assertDatabaseHas('policy_consents', [
        'user_id' => $user->id,
        'policy_id' => $policy->id,
        'context' => PolicyConsentContext::Subscription->value,
    ]);
});

test('subscription checkout does not record consents when product has no policies', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()->subscription()->approved()->visible()->active()->create();
    $product->categories()->attach($category);
    $price = Price::factory()->recurring()->active()->default()->for($product)->create([
        'is_visible' => true,
        'amount' => 1000,
        'external_price_id' => 'price_test123',
    ]);

    $this->mock(PaymentManager::class, function (MockInterface $mock): void {
        $mock->shouldReceive('currentSubscription')->andReturn(null);
        $mock->shouldReceive('startSubscription')->once()->andReturn('https://checkout.stripe.com/pay/cs_test');
    });

    $this->post(route('store.subscriptions.store'), [
        'price_id' => $price->id,
    ]);

    $this->assertDatabaseMissing('policy_consents', [
        'user_id' => $user->id,
    ]);
});
