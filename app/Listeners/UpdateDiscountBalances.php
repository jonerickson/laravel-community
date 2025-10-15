<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\DiscountType;
use App\Events\OrderSucceeded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class UpdateDiscountBalances implements ShouldQueue
{
    use Queueable;

    /**
     * @throws Throwable
     */
    public function handle(OrderSucceeded $event): void
    {
        DB::transaction(function () use ($event): void {
            $event->order->load('discounts');

            foreach ($event->order->discounts as $discount) {
                if ($discount->type === DiscountType::GiftCard) {
                    $discount->update([
                        'current_balance' => max(0, $discount->current_balance - $discount->pivot->amount_applied),
                        'times_used' => ++$discount->times_used,
                    ]);
                }
            }
        });
    }
}
