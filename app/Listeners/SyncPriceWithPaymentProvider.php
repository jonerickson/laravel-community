<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PriceCreated;
use App\Events\PriceDeleted;
use App\Events\PriceUpdated;
use App\Managers\PaymentManager;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SyncPriceWithPaymentProvider implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private readonly PaymentManager $paymentManager)
    {
        //
    }

    public function handle(PriceCreated|PriceUpdated|PriceDeleted $event): void
    {
        if (! $event->price->product->external_product_id) {
            Log::warning('Cannot sync price - product has no external_product_id', [
                'product_price_id' => $event->price->id,
                'product_id' => $event->price->product->id,
            ]);

            return;
        }

        try {
            match (true) {
                $event instanceof PriceCreated => $this->handleProductPriceCreated($event),
                $event instanceof PriceUpdated => $this->handleProductPriceUpdated($event),
                $event instanceof PriceDeleted => $this->handleProductPriceDeleted($event),
            };
        } catch (Exception $e) {
            Log::error('Failed to sync product price with payment provider', [
                'product_price_id' => $event->price->id,
                'product_id' => $event->price->product_id,
                'event_type' => $event::class,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function handleProductPriceCreated(PriceCreated $event): void
    {
        $price = $event->price;
        $product = $price->product;

        if ($price->external_price_id) {
            return;
        }

        $this->paymentManager->createPrice($product, $price);

        Log::info('Product price created in payment provider', [
            'product_price_id' => $price->id,
            'product_id' => $product->id,
        ]);
    }

    protected function handleProductPriceUpdated(PriceUpdated $event): void
    {
        $price = $event->price;
        $product = $price->product;

        if (! $price->external_price_id) {
            return;
        }

        $this->paymentManager->updatePrice($product, $price);

        Log::info('Product price updated in payment provider', [
            'product_price_id' => $price->id,
            'product_id' => $product->id,
        ]);
    }

    protected function handleProductPriceDeleted(PriceDeleted $event): void
    {
        $price = $event->price;
        $product = $price->product;

        if (! $price->external_price_id) {
            return;
        }

        $this->paymentManager->deletePrice($product, $price);

        Log::info('Product price deleted in payment provider', [
            'product_price_id' => $price->id,
            'product_id' => $product->id,
        ]);
    }
}
