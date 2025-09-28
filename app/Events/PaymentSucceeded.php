<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Queue\Queueable;

class PaymentSucceeded
{
    use Queueable;

    public function __construct(public Order $order)
    {
        //
    }
}
