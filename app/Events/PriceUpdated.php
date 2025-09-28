<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Price;
use Illuminate\Foundation\Queue\Queueable;

class PriceUpdated
{
    use Queueable;

    public function __construct(public Price $price) {}
}
