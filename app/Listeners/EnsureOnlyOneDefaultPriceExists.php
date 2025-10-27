<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PriceSaving;

class EnsureOnlyOneDefaultPriceExists
{
    public function handle(PriceSaving $event): void
    {
        if ($event->price->is_default) {
            $event->price->toggleDefaultPrice();
        }
    }
}
