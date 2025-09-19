<?php

declare(strict_types=1);

namespace App\Events\Stripe;

class PaymentActionRequired
{
    public function __construct(public array $payload)
    {
        //
    }
}
