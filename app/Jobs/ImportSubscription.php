<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Managers\PaymentManager;
use App\Models\Order;
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ImportSubscription implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public ?CarbonInterface $billingAnchorCycle,
    ) {}

    public function handle(PaymentManager $paymentManager): void
    {
        try {
            if (! $paymentManager->getCustomer($this->order->user) instanceof \App\Data\CustomerData && ! $paymentManager->createCustomer($this->order->user)) {
                throw new Exception('Failed to create Stripe customer.');
            }

            $paymentManager->startSubscription(
                order: $this->order,
                chargeNow: false,
                firstParty: false,
                anchorBillingCycle: $this->billingAnchorCycle,
            );
        } catch (Exception $e) {
            Log::error('Failed to import subscription', [
                'user_id' => $this->order->user_id,
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
