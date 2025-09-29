<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\SubscriptionStatus;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SubscriptionUpdated implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public ?SubscriptionStatus $status = null,
    ) {
        //
    }
}
