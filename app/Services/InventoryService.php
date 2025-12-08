<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InventoryAlertType;
use App\Enums\InventoryReservationStatus;
use App\Enums\InventoryTransactionType;
use App\Models\InventoryItem;
use App\Models\InventoryReservation;
use App\Models\InventoryTransaction;
use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;

class InventoryService
{
    /**
     * @throws Throwable
     */
    public function adjustStock(
        InventoryItem $inventoryItem,
        int $quantity,
        InventoryTransactionType $type,
        ?string $reason = null,
    ): InventoryTransaction {
        return DB::transaction(function () use ($inventoryItem, $quantity, $type, $reason) {
            $quantityBefore = $inventoryItem->quantity_available;

            $inventoryItem->increment('quantity_available', $quantity);
            $inventoryItem->refresh();

            $transaction = $inventoryItem->transactions()->create([
                'type' => $type,
                'quantity' => $quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $inventoryItem->quantity_available,
                'reason' => $reason,
            ]);

            $this->checkAndCreateAlerts($inventoryItem);

            return $transaction;
        });
    }

    /**
     * @throws Throwable
     */
    public function reserveInventory(Order $order): InventoryReservation
    {
        $order->loadMissing(['items.price.product.inventoryItem']);

        return DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $inventoryItem = $item->price?->product?->inventoryItem;

                if (! $inventoryItem) {
                    throw new Exception('Item not configured to track inventory');
                }

                if ($inventoryItem->quantity_available < $item->quantity) {
                    throw new Exception('Insufficient inventory to reserve');
                }

                $quantityBefore = $inventoryItem->quantity_available;

                $inventoryItem->decrement('quantity_available', $item->quantity);
                $inventoryItem->increment('quantity_reserved', $item->quantity);
                $inventoryItem->refresh();

                $inventoryItem->transactions()->create([
                    'type' => InventoryTransactionType::Reserved,
                    'quantity' => -$item->quantity,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $inventoryItem->quantity_available,
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                    'reason' => sprintf('Reserved for order #%s', $order->id),
                ]);

                $this->checkAndCreateAlerts($inventoryItem);

                return $inventoryItem->reservations()->create([
                    'order_id' => $order->id,
                    'quantity' => $item->quantity,
                    'status' => InventoryReservationStatus::Active,
                    'expires_at' => now()->addHours(24),
                ]);
            }
        });
    }

    /**
     * @throws Throwable
     */
    public function fulfillReservations(Order $order): void
    {
        $order->loadMissing(['items.price.product.inventoryItem.reservations']);

        DB::transaction(function () use ($order): void {
            foreach ($order->items as $item) {
                $inventoryItem = $item->price?->product?->inventoryItem;

                if (! $inventoryItem) {
                    continue;
                }

                foreach ($inventoryItem->reservations
                    ->filter(fn (InventoryReservation $reservation): bool => $reservation->order_id === $order->getKey())
                    ->filter(fn (InventoryReservation $reservation): bool => $reservation->status === InventoryReservationStatus::Active) as $reservation
                ) {
                    $quantityBefore = $inventoryItem->quantity_on_hand;

                    $inventoryItem->decrement('quantity_reserved', $reservation->quantity);
                    $inventoryItem->refresh();

                    $inventoryItem->transactions()->create([
                        'type' => InventoryTransactionType::Sale,
                        'quantity' => -$reservation->quantity,
                        'quantity_before' => $quantityBefore,
                        'quantity_after' => $inventoryItem->quantity_available,
                        'reference_type' => $reservation->order_id ? Order::class : null,
                        'reference_id' => $reservation->order_id,
                        'created_by' => $order->user_id,
                        'reason' => sprintf('Sale for order #%s', $order->id),
                    ]);

                    $reservation->update([
                        'status' => InventoryReservationStatus::Fulfilled,
                        'fulfilled_at' => now(),
                    ]);

                    $this->checkAndCreateAlerts($inventoryItem);
                }
            }
        });
    }

    /**
     * @throws Throwable
     */
    public function releaseReservations(Order $order): void
    {
        $order->loadMissing(['items.price.product.inventoryItem.reservations']);

        DB::transaction(function () use ($order): void {
            foreach ($order->items as $item) {
                $inventoryItem = $item->price?->product?->inventoryItem;

                if (! $inventoryItem) {
                    continue;
                }

                foreach ($inventoryItem->reservations
                    ->filter(fn (InventoryReservation $reservation): bool => $reservation->order_id === $order->getKey())
                    ->filter(fn (InventoryReservation $reservation): bool => $reservation->status === InventoryReservationStatus::Active) as $reservation
                ) {
                    $quantityBefore = $inventoryItem->quantity_available;

                    $inventoryItem->increment('quantity_available', $reservation->quantity);
                    $inventoryItem->decrement('quantity_reserved', $reservation->quantity);
                    $inventoryItem->refresh();

                    $inventoryItem->transactions()->create([
                        'type' => InventoryTransactionType::Released,
                        'quantity' => $reservation->quantity,
                        'quantity_before' => $quantityBefore,
                        'quantity_after' => $inventoryItem->quantity_available,
                        'reference_type' => $reservation->order_id ? Order::class : null,
                        'reference_id' => $reservation->order_id,
                        'reason' => sprintf('Released for order #%s', $order->id),
                    ]);

                    $reservation->update(['status' => InventoryReservationStatus::Cancelled]);

                    $this->checkAndCreateAlerts($inventoryItem);
                }
            }
        });
    }

    /**
     * @throws Throwable
     */
    public function recordReturn(InventoryItem $inventoryItem, int $quantity, int $orderId): void
    {
        $this->adjustStock(
            $inventoryItem,
            $quantity,
            InventoryTransactionType::Return,
            sprintf('Return from order #%s', $orderId),
        );
    }

    /**
     * @throws Throwable
     */
    public function markDamaged(InventoryItem $inventoryItem, int $quantity, ?string $reason = null): void
    {
        DB::transaction(function () use ($inventoryItem, $quantity, $reason): void {
            $quantityBefore = $inventoryItem->quantity_available;

            $inventoryItem->decrement('quantity_available', $quantity);
            $inventoryItem->increment('quantity_damaged', $quantity);
            $inventoryItem->refresh();

            $inventoryItem->transactions()->create([
                'type' => InventoryTransactionType::Damage,
                'quantity' => -$quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $inventoryItem->quantity_available,
                'reason' => $reason,
            ]);

            $this->checkAndCreateAlerts($inventoryItem);
        });
    }

    /**
     * @throws Throwable
     */
    public function restock(InventoryItem $inventoryItem, int $quantity, ?string $notes = null): void
    {
        $this->adjustStock(
            $inventoryItem,
            $quantity,
            InventoryTransactionType::Restock,
            $notes
        );
    }

    public function releaseExpiredReservations(): int
    {
        $expiredReservations = InventoryReservation::query()
            ->where('status', InventoryReservationStatus::Active)
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;

        foreach ($expiredReservations as $reservation) {
            $inventoryItem = $reservation->inventoryItem;

            $inventoryItem->increment('quantity_available', $reservation->quantity);
            $inventoryItem->decrement('quantity_reserved', $reservation->quantity);

            $reservation->update(['status' => InventoryReservationStatus::Expired]);

            $count++;
        }

        return $count;
    }

    protected function checkAndCreateAlerts(InventoryItem $inventoryItem): void
    {
        if ($inventoryItem->is_low_stock && ! $inventoryItem->is_out_of_stock) {
            $inventoryItem->alerts()->firstOrCreate([
                'alert_type' => InventoryAlertType::LowStock,
                'is_resolved' => false,
            ], [
                'threshold_value' => $inventoryItem->reorder_point,
                'current_value' => $inventoryItem->quantity_available,
            ]);
        }

        if ($inventoryItem->is_out_of_stock) {
            $inventoryItem->alerts()->firstOrCreate([
                'alert_type' => InventoryAlertType::OutOfStock,
                'is_resolved' => false,
            ], [
                'threshold_value' => 0,
                'current_value' => $inventoryItem->quantity_available,
            ]);
        }

        if ($inventoryItem->reorder_point && $inventoryItem->quantity_available > $inventoryItem->reorder_point) {
            $inventoryItem->alerts()
                ->where('is_resolved', false)
                ->update(['is_resolved' => true, 'resolved_at' => now()]);
        }
    }
}
