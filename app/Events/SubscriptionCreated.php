<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Queue\Queueable;

class SubscriptionCreated
{
    use Queueable;

    public function __construct()
    {
        //
    }
}
