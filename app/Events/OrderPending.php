<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class OrderPending extends ShouldBeStored
{
    public ?int $createdBy = null;

    public function __construct(public Order $order)
    {
        $this->createdBy = Auth::id();
    }
}
