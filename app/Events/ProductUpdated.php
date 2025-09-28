<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Product;
use Illuminate\Foundation\Queue\Queueable;

class ProductUpdated
{
    use Queueable;

    public function __construct(public Product $product) {}
}
