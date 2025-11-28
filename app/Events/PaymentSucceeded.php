<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\BillingReason;
use App\Models\Order;

class PaymentSucceeded
{
    public function __construct(public Order $order, public ?BillingReason $billingReason = null)
    {
        //
    }
}
