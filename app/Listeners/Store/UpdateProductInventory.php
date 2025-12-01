<?php

declare(strict_types=1);

namespace App\Listeners\Store;

use App\Events\OrderCancelled;
use App\Events\OrderRefunded;
use App\Events\OrderSucceeded;
use App\Models\Order;
use App\Services\InventoryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Throwable;

class UpdateProductInventory implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    /**
     * @throws Throwable
     */
    public function handle(OrderSucceeded|OrderRefunded|OrderCancelled $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        $inventoryService = app(InventoryService::class);

        $order = $event->order;

        match ($event::class) {
            OrderSucceeded::class => $this->handleOrderSucceeded($inventoryService, $order),
            OrderRefunded::class => $this->handleOrderRefunded($inventoryService, $order),
            OrderCancelled::class => $this->handleOrderCancelled($inventoryService, $order),
        };
    }

    /**
     * @throws Throwable
     */
    protected function handleOrderSucceeded(InventoryService $inventoryService, Order $order): void
    {
        $inventoryService->fulfillReservations($order);
    }

    /**
     * @throws Throwable
     */
    protected function handleOrderRefunded(InventoryService $inventoryService, Order $order): void
    {
        $orderItems = $order->items()
            ->with('price.product.inventoryItem')
            ->get();

        foreach ($orderItems as $item) {
            $inventoryItem = $item->price?->product?->inventoryItem;

            if (! $inventoryItem) {
                continue;
            }

            $inventoryService->recordReturn(
                inventoryItem: $inventoryItem,
                quantity: $item->quantity,
                orderId: $order->id,
            );
        }
    }

    /**
     * @throws Throwable
     */
    protected function handleOrderCancelled(InventoryService $inventoryService, Order $order): void
    {
        $inventoryService->releaseReservations($order);
    }
}
