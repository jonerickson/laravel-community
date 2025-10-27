<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\SubscriptionStatus;
use App\Models\Product;
use App\Models\User;

class SubscriptionUpdated
{
    public function __construct(
        public User $user,
        public Product $product,
        public ?SubscriptionStatus $currentStatus = null,
        public ?SubscriptionStatus $previousStatus = null,
    ) {
        //
    }
}
