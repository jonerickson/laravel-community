<?php

declare(strict_types=1);

namespace App\Events\Stripe;

class PaymentSucceeded
{
    public function __construct(public array $payload)
    {
        //
    }
}
