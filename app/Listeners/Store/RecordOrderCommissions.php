<?php

declare(strict_types=1);

namespace App\Listeners\Store;

use App\Actions\Commissions\RecordCommissionAction;
use App\Events\OrderSucceeded;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Throwable;

class RecordOrderCommissions implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    /**
     * @throws Throwable
     */
    public function handle(OrderSucceeded $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        $order = $event->order;

        foreach ($order->items as $item) {
            $product = $item->price->product;

            if (! $product) {
                continue;
            }

            if (! $product->seller_id) {
                continue;
            }

            $commissionRate = $product->commission_rate ?? 0;

            if ($commissionRate > 0) {
                $commissionAmount = $item->amount * $commissionRate;

                $seller = User::find($product->seller_id);

                if (! $seller instanceof User) {
                    continue;
                }

                RecordCommissionAction::execute($seller, $order, $commissionAmount);
            }
        }
    }
}
