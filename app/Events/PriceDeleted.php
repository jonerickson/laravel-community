<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Price;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PriceDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public Price $price) {}
}
