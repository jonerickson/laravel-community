<?php

declare(strict_types=1);

namespace App\Listeners\Store;

use App\Events\OrderSucceeded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Throwable;

class UpdateDiscountTimesUsed implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    /**
     * @throws Throwable
     */
    public function handle(OrderSucceeded $event): void
    {
        DB::transaction(function () use ($event): void {
            $event->order->load('discounts');

            foreach ($event->order->discounts as $discount) {
                $discount->update([
                    'times_used' => ++$discount->times_used,
                ]);
            }
        });
    }
}
