<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;

class OrderSaved
{
    public function __construct(public Order $order)
    {
        //
    }
}
