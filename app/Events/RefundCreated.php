<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\OrderRefundReason;
use App\Models\Order;
use Illuminate\Foundation\Queue\Queueable;

class RefundCreated
{
    use Queueable;

    public function __construct(
        public Order $order,
        public OrderRefundReason $reason,
        public ?string $notes,
    ) {
        //
    }
}
