<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Product;

class ProductCreated
{
    public function __construct(public Product $product) {}
}
