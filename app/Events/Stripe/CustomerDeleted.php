<?php

declare(strict_types=1);

namespace App\Events\Stripe;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class CustomerDeleted implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    public function __construct(public array $payload)
    {
        //
    }
}
