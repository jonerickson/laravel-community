<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\SubscriptionStatus;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionUpdated implements ShouldQueue
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order,
        public ?SubscriptionStatus $currentStatus = null,
        public ?SubscriptionStatus $previousStatus = null,
    ) {
        //
    }
}
