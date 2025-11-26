<?php

declare(strict_types=1);

namespace App\Listeners\Store;

use App\Events\PriceCreated;
use App\Events\PriceDeleted;
use App\Events\PriceUpdated;
use App\Managers\PaymentManager;
use Illuminate\Support\Facades\App;

class SyncPriceWithPaymentProvider
{
    public function handle(PriceCreated|PriceUpdated|PriceDeleted $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        $paymentManager = app(PaymentManager::class);

        if (! $event->price->product->external_product_id) {
            return;
        }

        match (true) {
            $event instanceof PriceCreated => $this->handleProductPriceCreated($event, $paymentManager),
            $event instanceof PriceUpdated => $this->handleProductPriceUpdated($event, $paymentManager),
            $event instanceof PriceDeleted => $this->handleProductPriceDeleted($event, $paymentManager),
        };
    }

    protected function handleProductPriceCreated(PriceCreated $event, PaymentManager $paymentManager): void
    {
        $price = $event->price;

        if ($price->external_price_id) {
            return;
        }

        $paymentManager->createPrice($price);
    }

    protected function handleProductPriceUpdated(PriceUpdated $event, PaymentManager $paymentManager): void
    {
        $price = $event->price;

        if (! $price->external_price_id) {
            return;
        }

        $paymentManager->updatePrice($price);
    }

    protected function handleProductPriceDeleted(PriceDeleted $event, PaymentManager $paymentManager): void
    {
        $price = $event->price;

        if (! $price->external_price_id) {
            return;
        }

        $paymentManager->deletePrice($price);
    }
}
