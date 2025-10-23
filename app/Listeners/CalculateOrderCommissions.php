<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderSucceeded;
use App\Mail\Marketplace\ProductSold;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class CalculateOrderCommissions implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    public function handle(OrderSucceeded $event): void
    {
        $order = $event->order;
        $sellerItems = [];

        foreach ($order->items as $item) {
            if (! $item->product) {
                continue;
            }
            if (! $item->product->seller_id) {
                continue;
            }

            $commissionRate = $item->product->commission_rate ?? 0;

            if ($commissionRate > 0) {
                $commissionAmount = $item->amount * $commissionRate;

                $item->update([
                    'commission_amount' => $commissionAmount,
                ]);

                $sellerId = $item->product->seller_id;
                if (! isset($sellerItems[$sellerId])) {
                    $sellerItems[$sellerId] = [];
                }
                $sellerItems[$sellerId][] = $item;
            }
        }

        foreach ($sellerItems as $items) {
            $seller = $items[0]->product->seller;

            if ($seller && $seller->email) {
                Mail::to($seller->email)->send(
                    new ProductSold($order, $seller, collect($items))
                );
            }
        }
    }
}
