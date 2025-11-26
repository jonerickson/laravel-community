<?php

declare(strict_types=1);

namespace App\Listeners\Store;

use App\Events\PriceSaving;
use Illuminate\Support\Facades\App;

class EnsureOnlyOneDefaultPriceExists
{
    public function handle(PriceSaving $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        if ($event->price->is_default) {
            $event->price->toggleDefaultPrice();
        }
    }
}
