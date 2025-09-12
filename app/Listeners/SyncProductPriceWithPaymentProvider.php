<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ProductPriceCreated;
use App\Events\ProductPriceDeleting;
use App\Events\ProductPriceUpdated;
use App\Managers\PaymentManager;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SyncProductPriceWithPaymentProvider implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(public PaymentManager $paymentManager) {}

    public function handle(ProductPriceCreated|ProductPriceUpdated|ProductPriceDeleting $event): void
    {
        try {
            match (true) {
                $event instanceof ProductPriceCreated => $this->handleProductPriceCreated($event),
                $event instanceof ProductPriceUpdated => $this->handleProductPriceUpdated($event),
                $event instanceof ProductPriceDeleting => $this->handleProductPriceDeleted($event),
            };
        } catch (Exception $e) {
            Log::error('Failed to sync product price with payment provider', [
                'product_price_id' => $event->productPrice->id,
                'product_id' => $event->productPrice->product_id,
                'event_type' => get_class($event),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function handleProductPriceCreated(ProductPriceCreated $event): void
    {
        if (filled($event->productPrice->external_price_id)) {
            return;
        }

        $productPrice = $event->productPrice;
        $product = $productPrice->product;

        if (! $product->external_product_id) {
            Log::warning('Cannot create price - product has no external_product_id', [
                'product_price_id' => $productPrice->id,
                'product_id' => $product->id,
            ]);

            return;
        }

        $this->paymentManager->createPrice($product, $productPrice);

        Log::info('Product price created in payment provider', [
            'product_price_id' => $productPrice->id,
            'product_id' => $product->id,
        ]);
    }

    protected function handleProductPriceUpdated(ProductPriceUpdated $event): void
    {
        $productPrice = $event->productPrice;
        $product = $productPrice->product;

        if (! $productPrice->external_price_id) {
            Log::warning('Cannot update price - no external_price_id', [
                'product_price_id' => $productPrice->id,
                'product_id' => $product->id,
            ]);

            return;
        }

        $this->paymentManager->updatePrice($product, $productPrice);

        Log::info('Product price updated in payment provider', [
            'product_price_id' => $productPrice->id,
            'product_id' => $product->id,
        ]);
    }

    protected function handleProductPriceDeleted(ProductPriceDeleting $event): void
    {
        $productPrice = $event->productPrice;
        $product = $productPrice->product;

        if (! $productPrice->external_price_id) {
            return;
        }

        $this->paymentManager->deletePrice($product, $productPrice);

        Log::info('Product price deleted in payment provider', [
            'product_price_id' => $productPrice->id,
            'product_id' => $product->id,
        ]);
    }
}
