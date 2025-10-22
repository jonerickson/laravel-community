<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Product;

class ProductDeleted
{
    public function __construct(public Product $product) {}
}
