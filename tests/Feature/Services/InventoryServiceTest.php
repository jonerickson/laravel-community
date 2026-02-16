<?php

declare(strict_types=1);

use App\Enums\InventoryAlertType;
use App\Enums\InventoryReservationStatus;
use App\Enums\InventoryTransactionType;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\InventoryService;

beforeEach(function (): void {
    $this->service = app(InventoryService::class);
});

/**
 * Helper to create an inventory item with a product.
 */
function createInventoryItem(array $attributes = []): InventoryItem
{
    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()->approved()->active()->visible()->create();
    $product->categories()->attach($category);

    return InventoryItem::create(array_merge([
        'product_id' => $product->id,
        'quantity_available' => 100,
        'quantity_reserved' => 0,
        'quantity_damaged' => 0,
        'reorder_point' => 10,
        'reorder_quantity' => 50,
        'track_inventory' => true,
        'allow_backorder' => false,
    ], $attributes));
}

/**
 * Helper to create an order with items that have inventory.
 */
function createOrderWithInventory(User $user, int $quantity = 2, ?InventoryItem $inventoryItem = null): Order
{
    $inventoryItem ??= createInventoryItem();

    $price = Price::factory()->oneTime()->active()->create([
        'product_id' => $inventoryItem->product_id,
        'amount' => 1000,
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'price_id' => $price->id,
        'quantity' => $quantity,
        'amount' => $price->amount,
    ]);

    return $order;
}

describe('adjustStock', function (): void {
    test('increments quantity_available', function (): void {
        $inventoryItem = createInventoryItem(['quantity_available' => 50]);

        $transaction = $this->service->adjustStock(
            $inventoryItem,
            25,
            InventoryTransactionType::Restock,
            'Test restock'
        );

        $inventoryItem->refresh();

        expect($inventoryItem->quantity_available)->toBe(75)
            ->and($transaction->quantity)->toBe(25)
            ->and($transaction->quantity_before)->toBe(50)
            ->and($transaction->quantity_after)->toBe(75)
            ->and($transaction->type)->toBe(InventoryTransactionType::Restock)
            ->and($transaction->reason)->toBe('Test restock');
    });

    test('decrements quantity_available with negative value', function (): void {
        $inventoryItem = createInventoryItem(['quantity_available' => 50]);

        $transaction = $this->service->adjustStock(
            $inventoryItem,
            -10,
            InventoryTransactionType::Adjustment,
            'Manual adjustment'
        );

        $inventoryItem->refresh();

        expect($inventoryItem->quantity_available)->toBe(40)
            ->and($transaction->quantity)->toBe(-10)
            ->and($transaction->quantity_before)->toBe(50)
            ->and($transaction->quantity_after)->toBe(40);
    });

    test('creates transaction record', function (): void {
        $inventoryItem = createInventoryItem();

        $this->service->adjustStock(
            $inventoryItem,
            10,
            InventoryTransactionType::Restock
        );

        expect($inventoryItem->transactions()->count())->toBe(1);
    });

    test('creates low stock alert when below reorder point', function (): void {
        $inventoryItem = createInventoryItem([
            'quantity_available' => 15,
            'reorder_point' => 10,
        ]);

        $this->service->adjustStock(
            $inventoryItem,
            -10,
            InventoryTransactionType::Adjustment
        );

        expect($inventoryItem->alerts()->where('alert_type', InventoryAlertType::LowStock)->exists())->toBeTrue();
    });

    test('creates out of stock alert when quantity is zero', function (): void {
        $inventoryItem = createInventoryItem([
            'quantity_available' => 5,
            'allow_backorder' => false,
        ]);

        $this->service->adjustStock(
            $inventoryItem,
            -5,
            InventoryTransactionType::Sale
        );

        expect($inventoryItem->alerts()->where('alert_type', InventoryAlertType::OutOfStock)->exists())->toBeTrue();
    });

    test('resolves alerts when stock goes above reorder point', function (): void {
        $inventoryItem = createInventoryItem([
            'quantity_available' => 5,
            'reorder_point' => 10,
        ]);

        $inventoryItem->alerts()->create([
            'alert_type' => InventoryAlertType::LowStock,
            'is_resolved' => false,
            'threshold_value' => 10,
            'current_value' => 5,
        ]);

        $this->service->adjustStock(
            $inventoryItem,
            20,
            InventoryTransactionType::Restock
        );

        expect($inventoryItem->alerts()->where('is_resolved', false)->exists())->toBeFalse();
    });
});

describe('reserveInventory', function (): void {
    test('reserves inventory for order', function (): void {
        $user = User::factory()->create();
        $inventoryItem = createInventoryItem(['quantity_available' => 100, 'quantity_reserved' => 0]);
        $order = createOrderWithInventory($user, 5, $inventoryItem);

        $reservation = $this->service->reserveInventory($order);

        $inventoryItem->refresh();

        expect($inventoryItem->quantity_available)->toBe(95)
            ->and($inventoryItem->quantity_reserved)->toBe(5)
            ->and($reservation->status)->toBe(InventoryReservationStatus::Active)
            ->and($reservation->quantity)->toBe(5)
            ->and($reservation->order_id)->toBe($order->id);
    });

    test('creates transaction for reservation', function (): void {
        $user = User::factory()->create();
        $inventoryItem = createInventoryItem(['quantity_available' => 100]);
        $order = createOrderWithInventory($user, 3, $inventoryItem);

        $this->service->reserveInventory($order);

        $transaction = $inventoryItem->transactions()->where('type', InventoryTransactionType::Reserved)->first();

        expect($transaction)->not->toBeNull()
            ->and($transaction->quantity)->toBe(-3)
            ->and($transaction->reference_type)->toBe(Order::class)
            ->and($transaction->reference_id)->toBe($order->id);
    });

    test('throws exception when item not configured to track inventory', function (): void {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->active()->visible()->create();
        $product = Product::factory()->approved()->active()->visible()->create();
        $product->categories()->attach($category);

        $price = Price::factory()->oneTime()->active()->create([
            'product_id' => $product->id,
        ]);

        $order = Order::factory()->create(['user_id' => $user->id]);
        OrderItem::create([
            'order_id' => $order->id,
            'price_id' => $price->id,
            'quantity' => 2,
            'amount' => $price->amount,
        ]);

        expect(fn () => $this->service->reserveInventory($order))
            ->toThrow(Exception::class, 'Item not configured to track inventory');
    });

    test('throws exception when insufficient inventory', function (): void {
        $user = User::factory()->create();
        $inventoryItem = createInventoryItem(['quantity_available' => 5]);
        $order = createOrderWithInventory($user, 10, $inventoryItem);

        expect(fn () => $this->service->reserveInventory($order))
            ->toThrow(Exception::class, 'Insufficient inventory to reserve');
    });

    test('sets expiration date on reservation', function (): void {
        $user = User::factory()->create();
        $inventoryItem = createInventoryItem(['quantity_available' => 100]);
        $order = createOrderWithInventory($user, 2, $inventoryItem);

        $reservation = $this->service->reserveInventory($order);

        expect($reservation->expires_at)->not->toBeNull()
            ->and($reservation->expires_at->greaterThan(now()->addHours(23)))->toBeTrue()
            ->and($reservation->expires_at->lessThanOrEqualTo(now()->addHours(24)))->toBeTrue();
    });
});

describe('fulfillReservations', function (): void {
    test('fulfills active reservations for order', function (): void {
        $user = User::factory()->create();
        $inventoryItem = createInventoryItem(['quantity_available' => 100, 'quantity_reserved' => 0]);
        $order = createOrderWithInventory($user, 5, $inventoryItem);

        $this->service->reserveInventory($order);
        $inventoryItem->refresh();

        expect($inventoryItem->quantity_available)->toBe(95)
            ->and($inventoryItem->quantity_reserved)->toBe(5);

        $this->service->fulfillReservations($order);
        $inventoryItem->refresh();

        expect($inventoryItem->quantity_reserved)->toBe(0);

        $reservation = $inventoryItem->reservations()->where('order_id', $order->id)->first();
        expect($reservation->status)->toBe(InventoryReservationStatus::Fulfilled)
            ->and($reservation->fulfilled_at)->not->toBeNull();
    });

    test('creates sale transaction when fulfilling', function (): void {
        $user = User::factory()->create();
        $inventoryItem = createInventoryItem(['quantity_available' => 100]);
        $order = createOrderWithInventory($user, 3, $inventoryItem);

        $this->service->reserveInventory($order);
        $this->service->fulfillReservations($order);

        $transaction = $inventoryItem->transactions()->where('type', InventoryTransactionType::Sale)->first();

        expect($transaction)->not->toBeNull()
            ->and($transaction->quantity)->toBe(-3)
            ->and($transaction->reference_id)->toBe($order->id);
    });

    test('skips items without inventory tracking', function (): void {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->active()->visible()->create();
        $product = Product::factory()->approved()->active()->visible()->create();
        $product->categories()->attach($category);

        $price = Price::factory()->oneTime()->active()->create([
            'product_id' => $product->id,
        ]);

        $order = Order::factory()->create(['user_id' => $user->id]);
        OrderItem::create([
            'order_id' => $order->id,
            'price_id' => $price->id,
            'quantity' => 2,
            'amount' => $price->amount,
        ]);

        // Should not throw, just skip
        $this->service->fulfillReservations($order);

        expect(true)->toBeTrue();
    });

    test('only fulfills active reservations', function (): void {
        $user = User::factory()->create();
        $inventoryItem = createInventoryItem(['quantity_available' => 100, 'quantity_reserved' => 5]);
        $order = createOrderWithInventory($user, 5, $inventoryItem);

        // Create a cancelled reservation
        $inventoryItem->reservations()->create([
            'order_id' => $order->id,
            'quantity' => 5,
            'status' => InventoryReservationStatus::Cancelled,
            'expires_at' => now()->addHours(24),
        ]);

        $this->service->fulfillReservations($order);

        // Cancelled reservation should remain unchanged
        $reservation = $inventoryItem->reservations()->where('order_id', $order->id)->first();
        expect($reservation->status)->toBe(InventoryReservationStatus::Cancelled);
    });
});

describe('releaseReservations', function (): void {
    test('releases active reservations for order', function (): void {
        $user = User::factory()->create();
        $inventoryItem = createInventoryItem(['quantity_available' => 100, 'quantity_reserved' => 0]);
        $order = createOrderWithInventory($user, 5, $inventoryItem);

        $this->service->reserveInventory($order);
        $inventoryItem->refresh();

        expect($inventoryItem->quantity_available)->toBe(95)
            ->and($inventoryItem->quantity_reserved)->toBe(5);

        $this->service->releaseReservations($order);
        $inventoryItem->refresh();

        expect($inventoryItem->quantity_available)->toBe(100)
            ->and($inventoryItem->quantity_reserved)->toBe(0);

        $reservation = $inventoryItem->reservations()->where('order_id', $order->id)->first();
        expect($reservation->status)->toBe(InventoryReservationStatus::Cancelled);
    });

    test('creates released transaction', function (): void {
        $user = User::factory()->create();
        $inventoryItem = createInventoryItem(['quantity_available' => 100]);
        $order = createOrderWithInventory($user, 3, $inventoryItem);

        $this->service->reserveInventory($order);
        $this->service->releaseReservations($order);

        $transaction = $inventoryItem->transactions()->where('type', InventoryTransactionType::Released)->first();

        expect($transaction)->not->toBeNull()
            ->and($transaction->quantity)->toBe(3)
            ->and($transaction->reference_id)->toBe($order->id);
    });

    test('skips items without inventory tracking', function (): void {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->active()->visible()->create();
        $product = Product::factory()->approved()->active()->visible()->create();
        $product->categories()->attach($category);

        $price = Price::factory()->oneTime()->active()->create([
            'product_id' => $product->id,
        ]);

        $order = Order::factory()->create(['user_id' => $user->id]);
        OrderItem::create([
            'order_id' => $order->id,
            'price_id' => $price->id,
            'quantity' => 2,
            'amount' => $price->amount,
        ]);

        // Should not throw, just skip
        $this->service->releaseReservations($order);

        expect(true)->toBeTrue();
    });

    test('only releases active reservations', function (): void {
        $user = User::factory()->create();
        $inventoryItem = createInventoryItem(['quantity_available' => 100, 'quantity_reserved' => 0]);
        $order = createOrderWithInventory($user, 5, $inventoryItem);

        // Create a fulfilled reservation
        $inventoryItem->reservations()->create([
            'order_id' => $order->id,
            'quantity' => 5,
            'status' => InventoryReservationStatus::Fulfilled,
            'expires_at' => now()->addHours(24),
            'fulfilled_at' => now(),
        ]);

        $this->service->releaseReservations($order);

        // Fulfilled reservation should remain unchanged
        $reservation = $inventoryItem->reservations()->where('order_id', $order->id)->first();
        expect($reservation->status)->toBe(InventoryReservationStatus::Fulfilled);
    });
});

describe('recordReturn', function (): void {
    test('increases quantity_available', function (): void {
        $inventoryItem = createInventoryItem(['quantity_available' => 50]);

        $this->service->recordReturn($inventoryItem, 5, 123);

        $inventoryItem->refresh();

        expect($inventoryItem->quantity_available)->toBe(55);
    });

    test('creates return transaction with order reference', function (): void {
        $inventoryItem = createInventoryItem();

        $this->service->recordReturn($inventoryItem, 3, 456);

        $transaction = $inventoryItem->transactions()->where('type', InventoryTransactionType::Return)->first();

        expect($transaction)->not->toBeNull()
            ->and($transaction->quantity)->toBe(3)
            ->and($transaction->reason)->toContain('456');
    });
});

describe('markDamaged', function (): void {
    test('decreases quantity_available and increases quantity_damaged', function (): void {
        $inventoryItem = createInventoryItem([
            'quantity_available' => 100,
            'quantity_damaged' => 0,
        ]);

        $this->service->markDamaged($inventoryItem, 10, 'Shipping damage');

        $inventoryItem->refresh();

        expect($inventoryItem->quantity_available)->toBe(90)
            ->and($inventoryItem->quantity_damaged)->toBe(10);
    });

    test('creates damage transaction', function (): void {
        $inventoryItem = createInventoryItem();

        $this->service->markDamaged($inventoryItem, 5, 'Water damage');

        $transaction = $inventoryItem->transactions()->where('type', InventoryTransactionType::Damage)->first();

        expect($transaction)->not->toBeNull()
            ->and($transaction->quantity)->toBe(-5)
            ->and($transaction->reason)->toBe('Water damage');
    });

    test('creates out of stock alert if all items damaged', function (): void {
        $inventoryItem = createInventoryItem([
            'quantity_available' => 10,
            'allow_backorder' => false,
        ]);

        $this->service->markDamaged($inventoryItem, 10);

        expect($inventoryItem->alerts()->where('alert_type', InventoryAlertType::OutOfStock)->exists())->toBeTrue();
    });
});

describe('restock', function (): void {
    test('increases quantity_available', function (): void {
        $inventoryItem = createInventoryItem(['quantity_available' => 50]);

        $this->service->restock($inventoryItem, 30, 'Vendor shipment arrived');

        $inventoryItem->refresh();

        expect($inventoryItem->quantity_available)->toBe(80);
    });

    test('creates restock transaction', function (): void {
        $inventoryItem = createInventoryItem();

        $this->service->restock($inventoryItem, 25, 'Weekly restock');

        $transaction = $inventoryItem->transactions()->where('type', InventoryTransactionType::Restock)->first();

        expect($transaction)->not->toBeNull()
            ->and($transaction->quantity)->toBe(25)
            ->and($transaction->reason)->toBe('Weekly restock');
    });

    test('resolves low stock alert after restock', function (): void {
        $inventoryItem = createInventoryItem([
            'quantity_available' => 5,
            'reorder_point' => 10,
        ]);

        $inventoryItem->alerts()->create([
            'alert_type' => InventoryAlertType::LowStock,
            'is_resolved' => false,
            'threshold_value' => 10,
            'current_value' => 5,
        ]);

        $this->service->restock($inventoryItem, 50);

        expect($inventoryItem->alerts()->where('is_resolved', false)->exists())->toBeFalse();
    });
});

describe('releaseExpiredReservations', function (): void {
    test('releases expired reservations', function (): void {
        $user = User::factory()->create();
        $inventoryItem = createInventoryItem([
            'quantity_available' => 95,
            'quantity_reserved' => 5,
        ]);

        $inventoryItem->reservations()->create([
            'order_id' => null,
            'quantity' => 5,
            'status' => InventoryReservationStatus::Active,
            'expires_at' => now()->subHour(),
        ]);

        $count = $this->service->releaseExpiredReservations();

        $inventoryItem->refresh();

        expect($count)->toBe(1)
            ->and($inventoryItem->quantity_available)->toBe(100)
            ->and($inventoryItem->quantity_reserved)->toBe(0);
    });

    test('sets status to expired', function (): void {
        $inventoryItem = createInventoryItem([
            'quantity_available' => 90,
            'quantity_reserved' => 10,
        ]);

        $reservation = $inventoryItem->reservations()->create([
            'order_id' => null,
            'quantity' => 10,
            'status' => InventoryReservationStatus::Active,
            'expires_at' => now()->subMinute(),
        ]);

        $this->service->releaseExpiredReservations();

        $reservation->refresh();

        expect($reservation->status)->toBe(InventoryReservationStatus::Expired);
    });

    test('does not release non-expired reservations', function (): void {
        $inventoryItem = createInventoryItem([
            'quantity_available' => 95,
            'quantity_reserved' => 5,
        ]);

        $inventoryItem->reservations()->create([
            'order_id' => null,
            'quantity' => 5,
            'status' => InventoryReservationStatus::Active,
            'expires_at' => now()->addHour(),
        ]);

        $count = $this->service->releaseExpiredReservations();

        $inventoryItem->refresh();

        expect($count)->toBe(0)
            ->and($inventoryItem->quantity_available)->toBe(95)
            ->and($inventoryItem->quantity_reserved)->toBe(5);
    });

    test('does not release already cancelled reservations', function (): void {
        $inventoryItem = createInventoryItem([
            'quantity_available' => 100,
            'quantity_reserved' => 0,
        ]);

        $inventoryItem->reservations()->create([
            'order_id' => null,
            'quantity' => 5,
            'status' => InventoryReservationStatus::Cancelled,
            'expires_at' => now()->subHour(),
        ]);

        $count = $this->service->releaseExpiredReservations();

        expect($count)->toBe(0);
    });

    test('handles multiple expired reservations', function (): void {
        $inventoryItem1 = createInventoryItem([
            'quantity_available' => 90,
            'quantity_reserved' => 10,
        ]);

        $inventoryItem2 = createInventoryItem([
            'quantity_available' => 85,
            'quantity_reserved' => 15,
        ]);

        $inventoryItem1->reservations()->create([
            'order_id' => null,
            'quantity' => 10,
            'status' => InventoryReservationStatus::Active,
            'expires_at' => now()->subHour(),
        ]);

        $inventoryItem2->reservations()->create([
            'order_id' => null,
            'quantity' => 15,
            'status' => InventoryReservationStatus::Active,
            'expires_at' => now()->subMinutes(30),
        ]);

        $count = $this->service->releaseExpiredReservations();

        $inventoryItem1->refresh();
        $inventoryItem2->refresh();

        expect($count)->toBe(2)
            ->and($inventoryItem1->quantity_available)->toBe(100)
            ->and($inventoryItem1->quantity_reserved)->toBe(0)
            ->and($inventoryItem2->quantity_available)->toBe(100)
            ->and($inventoryItem2->quantity_reserved)->toBe(0);
    });
});

describe('checkAndCreateAlerts', function (): void {
    test('creates low stock alert when quantity at reorder point', function (): void {
        $inventoryItem = createInventoryItem([
            'quantity_available' => 15,
            'reorder_point' => 10,
        ]);

        $this->service->adjustStock($inventoryItem, -5, InventoryTransactionType::Sale);

        $alert = $inventoryItem->alerts()->where('alert_type', InventoryAlertType::LowStock)->first();

        expect($alert)->not->toBeNull()
            ->and($alert->threshold_value)->toBe(10)
            ->and($alert->current_value)->toBe(10)
            ->and($alert->is_resolved)->toBeFalse();
    });

    test('does not duplicate low stock alert', function (): void {
        $inventoryItem = createInventoryItem([
            'quantity_available' => 8,
            'reorder_point' => 10,
        ]);

        // Create initial alert
        $this->service->adjustStock($inventoryItem, -1, InventoryTransactionType::Sale);
        // Trigger another adjustment
        $this->service->adjustStock($inventoryItem, -1, InventoryTransactionType::Sale);

        expect($inventoryItem->alerts()->where('alert_type', InventoryAlertType::LowStock)->count())->toBe(1);
    });

    test('does not create low stock alert when above reorder point', function (): void {
        $inventoryItem = createInventoryItem([
            'quantity_available' => 50,
            'reorder_point' => 10,
        ]);

        $this->service->adjustStock($inventoryItem, -5, InventoryTransactionType::Sale);

        expect($inventoryItem->alerts()->where('alert_type', InventoryAlertType::LowStock)->exists())->toBeFalse();
    });

    test('does not create low stock alert when no reorder point set', function (): void {
        $inventoryItem = createInventoryItem([
            'quantity_available' => 5,
            'reorder_point' => null,
        ]);

        $this->service->adjustStock($inventoryItem, -2, InventoryTransactionType::Sale);

        expect($inventoryItem->alerts()->where('alert_type', InventoryAlertType::LowStock)->exists())->toBeFalse();
    });

    test('does not create out of stock alert when backorders allowed', function (): void {
        $inventoryItem = createInventoryItem([
            'quantity_available' => 5,
            'allow_backorder' => true,
        ]);

        $this->service->adjustStock($inventoryItem, -5, InventoryTransactionType::Sale);

        expect($inventoryItem->alerts()->where('alert_type', InventoryAlertType::OutOfStock)->exists())->toBeFalse();
    });
});

describe('InventoryItem model', function (): void {
    test('quantity_on_hand is sum of available and reserved', function (): void {
        $inventoryItem = createInventoryItem([
            'quantity_available' => 50,
            'quantity_reserved' => 20,
        ]);

        expect($inventoryItem->quantity_on_hand)->toBe(70);
    });

    test('is_low_stock is true when at or below reorder point', function (): void {
        $inventoryItem = createInventoryItem([
            'quantity_available' => 10,
            'reorder_point' => 10,
        ]);

        expect($inventoryItem->is_low_stock)->toBeTrue();
    });

    test('is_low_stock is false when above reorder point', function (): void {
        $inventoryItem = createInventoryItem([
            'quantity_available' => 50,
            'reorder_point' => 10,
        ]);

        expect($inventoryItem->is_low_stock)->toBeFalse();
    });

    test('is_out_of_stock is true when quantity is zero and no backorder', function (): void {
        $inventoryItem = createInventoryItem([
            'quantity_available' => 0,
            'allow_backorder' => false,
        ]);

        expect($inventoryItem->is_out_of_stock)->toBeTrue();
    });

    test('is_out_of_stock is false when backorders allowed', function (): void {
        $inventoryItem = createInventoryItem([
            'quantity_available' => 0,
            'allow_backorder' => true,
        ]);

        expect($inventoryItem->is_out_of_stock)->toBeFalse();
    });

    test('canFulfillQuantity returns true when enough stock', function (): void {
        $inventoryItem = createInventoryItem([
            'quantity_available' => 50,
            'track_inventory' => true,
        ]);

        expect($inventoryItem->canFulfillQuantity(25))->toBeTrue();
    });

    test('canFulfillQuantity returns false when not enough stock', function (): void {
        $inventoryItem = createInventoryItem([
            'quantity_available' => 10,
            'track_inventory' => true,
            'allow_backorder' => false,
        ]);

        expect($inventoryItem->canFulfillQuantity(25))->toBeFalse();
    });

    test('canFulfillQuantity returns true when backorder allowed', function (): void {
        $inventoryItem = createInventoryItem([
            'quantity_available' => 10,
            'track_inventory' => true,
            'allow_backorder' => true,
        ]);

        expect($inventoryItem->canFulfillQuantity(25))->toBeTrue();
    });

    test('canFulfillQuantity returns true when not tracking inventory', function (): void {
        $inventoryItem = createInventoryItem([
            'quantity_available' => 0,
            'track_inventory' => false,
        ]);

        expect($inventoryItem->canFulfillQuantity(100))->toBeTrue();
    });
});
