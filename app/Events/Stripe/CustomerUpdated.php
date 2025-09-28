<?php

declare(strict_types=1);

namespace App\Events\Stripe;

use Illuminate\Foundation\Queue\Queueable;

class CustomerUpdated
{
    use Queueable;

    public function __construct(public array $payload)
    {
        //
    }
}
