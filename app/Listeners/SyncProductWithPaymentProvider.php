<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Managers\PaymentManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncProductWithPaymentProvider implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly PaymentManager $paymentManager)
    {
        //
    }

    public function handle(ProductCreated|ProductUpdated|ProductDeleted $event): void
    {
        match (true) {
            $event instanceof ProductCreated => $this->handleProductCreated($event),
            $event instanceof ProductUpdated => $this->handleProductUpdated($event),
            $event instanceof ProductDeleted => $this->handleProductDeleted($event),
        };
    }

    protected function handleProductCreated(ProductCreated $event): void
    {
        if ($event->product->external_product_id) {
            return;
        }

        $this->paymentManager->createProduct($event->product);
    }

    protected function handleProductUpdated(ProductUpdated $event): void
    {
        if (! $event->product->external_product_id) {
            return;
        }

        $this->paymentManager->updateProduct($event->product);
    }

    protected function handleProductDeleted(ProductDeleted $event): void
    {
        if (! $event->product->external_product_id) {
            return;
        }

        $this->paymentManager->deleteProduct($event->product);
    }
}
