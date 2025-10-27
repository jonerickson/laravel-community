<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;

class InvoiceCreated
{
    public function __construct(public Order $order)
    {
        //
    }
}
