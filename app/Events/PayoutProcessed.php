<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Payout;

class PayoutProcessed
{
    public function __construct(public Payout $payout)
    {
        //
    }
}
