<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\OrderRefundReason;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RefundCreated implements ShouldQueue
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order,
        public OrderRefundReason $reason,
        public ?string $notes,
    ) {
        //
    }
}
