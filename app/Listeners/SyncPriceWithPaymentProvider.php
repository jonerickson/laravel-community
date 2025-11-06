<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PriceCreated;
use App\Events\PriceDeleted;
use App\Events\PriceUpdated;
use App\Managers\PaymentManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;

class SyncPriceWithPaymentProvider implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    public function __construct(
        private readonly PaymentManager $paymentManager
    ) {
        //
    }

    public function handle(PriceCreated|PriceUpdated|PriceDeleted $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

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
