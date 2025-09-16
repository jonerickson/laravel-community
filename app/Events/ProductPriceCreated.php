<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Price;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductPriceCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Price $productPrice) {}
}
