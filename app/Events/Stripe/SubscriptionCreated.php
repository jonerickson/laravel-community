<?php

declare(strict_types=1);

namespace App\Events\Stripe;

class SubscriptionCreated
{
    public function __construct(public array $payload)
    {
        //
    }
}
