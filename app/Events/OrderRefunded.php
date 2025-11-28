<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;

class OrderRefunded
{
    public function __construct(public Order $order)
    {
        //
    }
}
