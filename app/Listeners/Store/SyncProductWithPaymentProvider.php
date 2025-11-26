<?php

declare(strict_types=1);

namespace App\Listeners\Store;

use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Managers\PaymentManager;
use Illuminate\Support\Facades\App;

class SyncProductWithPaymentProvider
{
    public function handle(ProductCreated|ProductUpdated|ProductDeleted $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        $paymentManager = app(PaymentManager::class);

        match (true) {
            $event instanceof ProductCreated => $this->handleProductCreated($event, $paymentManager),
            $event instanceof ProductUpdated => $this->handleProductUpdated($event, $paymentManager),
            $event instanceof ProductDeleted => $this->handleProductDeleted($event, $paymentManager),
        };
    }

    protected function handleProductCreated(ProductCreated $event, PaymentManager $paymentManager): void
    {
        if ($event->product->external_product_id) {
            return;
        }

        $paymentManager->createProduct($event->product);
    }

    protected function handleProductUpdated(ProductUpdated $event, PaymentManager $paymentManager): void
    {
        if (! $event->product->external_product_id) {
            return;
        }

        $paymentManager->updateProduct($event->product);
    }

    protected function handleProductDeleted(ProductDeleted $event, PaymentManager $paymentManager): void
    {
        if (! $event->product->external_product_id) {
            return;
        }

        $paymentManager->deleteProduct($event->product);
    }
}
