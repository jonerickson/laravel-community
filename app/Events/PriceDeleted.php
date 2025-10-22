<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Price;

class PriceDeleted
{
    public function __construct(public Price $price) {}
}
