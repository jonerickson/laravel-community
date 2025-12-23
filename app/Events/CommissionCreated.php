<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Commission;

class CommissionCreated
{
    public function __construct(public Commission $commission)
    {
        //
    }
}
