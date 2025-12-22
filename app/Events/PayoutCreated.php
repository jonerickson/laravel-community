<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Payout;

class PayoutCreated
{
    public function __construct(public Payout $payout)
    {
        //
    }
}
