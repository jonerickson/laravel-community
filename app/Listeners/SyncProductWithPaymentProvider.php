<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ProductCreated;
use App\Events\ProductDeleting;
use App\Events\ProductUpdated;
use App\Managers\PaymentManager;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SyncProductWithPaymentProvider implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(public PaymentManager $paymentManager) {}

    public function handle(ProductCreated|ProductUpdated|ProductDeleting $event): void
    {
        try {
            match (true) {
                $event instanceof ProductCreated => $this->handleProductCreated($event),
                $event instanceof ProductUpdated => $this->handleProductUpdated($event),
                $event instanceof ProductDeleting => $this->handleProductDeleted($event),
            };
        } catch (Exception $e) {
            Log::error('Failed to sync product with payment provider', [
                'product_id' => $event->product->id,
                'event_type' => get_class($event),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function handleProductCreated(ProductCreated $event): void
    {
        $this->paymentManager->createProduct($event->product);
        Log::info('Product created in payment provider', ['product_id' => $event->product->id]);
    }

    protected function handleProductUpdated(ProductUpdated $event): void
    {
        if (! $event->product->external_product_id) {
            return;
        }

        $this->paymentManager->updateProduct($event->product);
        Log::info('Product updated in payment provider', ['product_id' => $event->product->id]);
    }

    protected function handleProductDeleted(ProductDeleting $event): void
    {
        if (! $event->product->external_product_id) {
            return;
        }

        $this->paymentManager->deleteProduct($event->product);
        Log::info('Product deleted in payment provider', ['product_id' => $event->product->id]);
    }
}
