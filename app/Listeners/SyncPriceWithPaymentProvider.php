<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PriceCreated;
use App\Events\PriceDeleted;
use App\Events\PriceUpdated;
use App\Managers\PaymentManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncPriceWithPaymentProvider implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly PaymentManager $paymentManager)
    {
        //
    }

    public function handle(PriceCreated|PriceUpdated|PriceDeleted $event): void
    {
        if (! $event->price->product->external_product_id) {
            return;
        }

        match (true) {
            $event instanceof PriceCreated => $this->handleProductPriceCreated($event),
            $event instanceof PriceUpdated => $this->handleProductPriceUpdated($event),
            $event instanceof PriceDeleted => $this->handleProductPriceDeleted($event),
        };
    }

    protected function handleProductPriceCreated(PriceCreated $event): void
    {
        $price = $event->price;

        if ($price->external_price_id) {
            return;
        }

        $this->paymentManager->createPrice($price);
    }

    protected function handleProductPriceUpdated(PriceUpdated $event): void
    {
        $price = $event->price;

        if (! $price->external_price_id) {
            return;
        }

        $this->paymentManager->updatePrice($price);
    }

    protected function handleProductPriceDeleted(PriceDeleted $event): void
    {
        $price = $event->price;

        if (! $price->external_price_id) {
            return;
        }

        $this->paymentManager->deletePrice($price);
    }
}
