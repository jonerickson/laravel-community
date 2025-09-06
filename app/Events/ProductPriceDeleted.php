<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ProductPrice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductPriceDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public ProductPrice $productPrice) {}
}
