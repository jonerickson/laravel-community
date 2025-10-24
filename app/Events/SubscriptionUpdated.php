<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\SubscriptionStatus;
use App\Models\Order;

class SubscriptionUpdated
{
    public function __construct(
        public Order $order,
        public ?SubscriptionStatus $currentStatus = null,
        public ?SubscriptionStatus $previousStatus = null,
    ) {
        //
    }
}
