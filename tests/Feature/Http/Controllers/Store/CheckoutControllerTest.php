<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Managers\PaymentManager;
use App\Models\Order;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Support\Facades\URL;

test('checkout success page requires authentication', function (): void {
    $user = User::factory()->create();

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $url = URL::signedRoute('store.checkout.success', ['order' => $order->reference_id]);

    $response = $this->get($url);

    $response->assertRedirect(route('login'));
});

test('checkout success page requires email verification', function (): void {
    $user = User::factory()->unverified()->create();

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $url = URL::signedRoute('store.checkout.success', ['order' => $order->reference_id]);

    $response = $this->actingAs($user)->get($url);

    $response->assertRedirect(route('verification.notice'));
});

test('checkout success page requires valid signature', function (): void {
    $user = User::factory()->create();

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $response = $this->actingAs($user)->get('/store/checkout/success/'.$order->reference_id);

    $response->assertForbidden();
});

test('checkout success calls payment manager processCheckoutSuccess', function (): void {
    $user = User::factory()->create();

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $this->mock(PaymentManager::class, function ($mock) use ($order): void {
        $mock->shouldReceive('processCheckoutSuccess')
            ->once()
            ->with(
                Mockery::type(Illuminate\Http\Request::class),
                Mockery::on(fn ($arg): bool => $arg->id === $order->id)
            )
            ->andReturn(true);
    });

    $url = URL::signedRoute('store.checkout.success', ['order' => $order->reference_id]);

    $response = $this->actingAs($user)->get($url);

    $response->assertRedirect(route('settings.orders'));
    $response->assertSessionHas('message', 'The order was successfully processed.');
});

test('checkout success redirects to custom redirect url when provided', function (): void {
    $user = User::factory()->create();

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $this->mock(PaymentManager::class, function ($mock): void {
        $mock->shouldReceive('processCheckoutSuccess')
            ->once()
            ->andReturn(true);
    });

    // The redirect param must be included in the signed route so it's part of the signature
    $url = URL::signedRoute('store.checkout.success', [
        'order' => $order->reference_id,
        'redirect' => urlencode('/custom/redirect/path'),
    ]);

    $response = $this->actingAs($user)->get($url);

    $response->assertRedirect('/custom/redirect/path');
});

test('checkout success returns 404 for non-existent order', function (): void {
    $user = User::factory()->create();

    $url = URL::signedRoute('store.checkout.success', ['order' => 'non-existent-uuid']);

    $response = $this->actingAs($user)->get($url);

    $response->assertNotFound();
});

test('checkout cancel page requires authentication', function (): void {
    $user = User::factory()->create();

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $url = URL::signedRoute('store.checkout.cancel', ['order' => $order->reference_id]);

    $response = $this->get($url);

    $response->assertRedirect(route('login'));
});

test('checkout cancel page requires email verification', function (): void {
    $user = User::factory()->unverified()->create();

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $url = URL::signedRoute('store.checkout.cancel', ['order' => $order->reference_id]);

    $response = $this->actingAs($user)->get($url);

    $response->assertRedirect(route('verification.notice'));
});

test('checkout cancel page requires valid signature', function (): void {
    $user = User::factory()->create();

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $response = $this->actingAs($user)->get('/store/checkout/cancel/'.$order->reference_id);

    $response->assertForbidden();
});

test('checkout cancel calls payment manager processCheckoutCancel', function (): void {
    $user = User::factory()->create();

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $this->mock(PaymentManager::class, function ($mock) use ($order): void {
        $mock->shouldReceive('processCheckoutCancel')
            ->once()
            ->with(
                Mockery::type(Illuminate\Http\Request::class),
                Mockery::on(fn ($arg): bool => $arg->id === $order->id)
            )
            ->andReturn(true);
    });

    $this->mock(InventoryService::class, function ($mock): void {
        $mock->shouldReceive('releaseReservations')
            ->once()
            ->andReturn(null);
    });

    $url = URL::signedRoute('store.checkout.cancel', ['order' => $order->reference_id]);

    $response = $this->actingAs($user)->get($url);

    $response->assertRedirect(route('store.cart.index'));
    $response->assertSessionHas('message', 'The order was successfully cancelled.');
});

test('checkout cancel releases inventory reservations', function (): void {
    $user = User::factory()->create();

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $this->mock(PaymentManager::class, function ($mock): void {
        $mock->shouldReceive('processCheckoutCancel')
            ->once()
            ->andReturn(true);
    });

    $this->mock(InventoryService::class, function ($mock) use ($order): void {
        $mock->shouldReceive('releaseReservations')
            ->once()
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $order->id))
            ->andReturn(null);
    });

    $url = URL::signedRoute('store.checkout.cancel', ['order' => $order->reference_id]);

    $response = $this->actingAs($user)->get($url);

    $response->assertRedirect(route('store.cart.index'));
});

test('checkout cancel continues even if inventory release fails', function (): void {
    $user = User::factory()->create();

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $this->mock(PaymentManager::class, function ($mock): void {
        $mock->shouldReceive('processCheckoutCancel')
            ->once()
            ->andReturn(true);
    });

    $this->mock(InventoryService::class, function ($mock): void {
        $mock->shouldReceive('releaseReservations')
            ->once()
            ->andThrow(new Exception('Failed to release inventory'));
    });

    $url = URL::signedRoute('store.checkout.cancel', ['order' => $order->reference_id]);

    $response = $this->actingAs($user)->get($url);

    // Should still redirect even if inventory release fails
    $response->assertRedirect(route('store.cart.index'));
    $response->assertSessionHas('message', 'The order was successfully cancelled.');
});

test('checkout cancel redirects to custom redirect url when provided', function (): void {
    $user = User::factory()->create();

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $this->mock(PaymentManager::class, function ($mock): void {
        $mock->shouldReceive('processCheckoutCancel')
            ->once()
            ->andReturn(true);
    });

    $this->mock(InventoryService::class, function ($mock): void {
        $mock->shouldReceive('releaseReservations')
            ->once()
            ->andReturn(null);
    });

    // The redirect param must be included in the signed route so it's part of the signature
    $url = URL::signedRoute('store.checkout.cancel', [
        'order' => $order->reference_id,
        'redirect' => urlencode('/custom/cancel/path'),
    ]);

    $response = $this->actingAs($user)->get($url);

    $response->assertRedirect('/custom/cancel/path');
});

test('checkout cancel returns 404 for non-existent order', function (): void {
    $user = User::factory()->create();

    $url = URL::signedRoute('store.checkout.cancel', ['order' => 'non-existent-uuid']);

    $response = $this->actingAs($user)->get($url);

    $response->assertNotFound();
});
