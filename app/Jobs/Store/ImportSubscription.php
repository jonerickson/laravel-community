<?php

declare(strict_types=1);

namespace App\Jobs\Store;

use App\Enums\ProrationBehavior;
use App\Managers\PaymentManager;
use App\Models\Order;
use App\Pipes\Stripe\EnsureCustomerExists;
use App\Pipes\Stripe\EnsureDefaultPaymentMethod;
use App\Pipes\Stripe\EnsurePricesExist;
use App\Pipes\Stripe\EnsureProductsExist;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Pipeline;
use Throwable;

class ImportSubscription implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public ?ProrationBehavior $prorationBehavior = ProrationBehavior::None,
        public ?CarbonInterface $backdateStartDate = null,
        public ?CarbonInterface $billingCycleAnchor = null,
    ) {}

    public function handle(PaymentManager $paymentManager): void
    {
        try {
            $order = Pipeline::send($this->order)
                ->through([
                    EnsureCustomerExists::class,
                    EnsureDefaultPaymentMethod::class,
                    EnsureProductsExist::class,
                    EnsurePricesExist::class,
                ])
                ->thenReturn();

            $paymentManager->startSubscription(
                order: $order,
                firstParty: false,
                prorationBehavior: $this->prorationBehavior,
                backdateStartDate: $this->backdateStartDate,
                billingCycleAnchor: $this->billingCycleAnchor,
                subscriptionOptions: [
                    'trial_settings' => [
                        'end_behavior' => [
                            'missing_payment_method' => 'create_invoice',
                        ],
                    ],
                ]
            );
        } catch (Throwable $throwable) {
            Log::error('Failed to import subscription', [
                'user_id' => $this->order->user_id,
                'order_id' => $this->order->id,
                'error' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);
        }
    }
}
