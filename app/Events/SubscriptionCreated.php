<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Product;
use App\Models\User;

class SubscriptionCreated
{
    public function __construct(public User $user, public Product $product)
    {
        //
    }
}
