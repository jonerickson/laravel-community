<?php

declare(strict_types=1);

namespace App\Listeners\Store;

use App\Actions\Commissions\RecordCommissionAction;
use App\Events\OrderSucceeded;
use App\Mail\Marketplace\ProductSold;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CalculateOrderCommissions implements ShouldQueue
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
        $sellerItems = [];

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

                if ($seller) {
                    RecordCommissionAction::execute($seller, $order, $commissionAmount);
                }

                if (! isset($sellerItems[$seller->getKey()])) {
                    $sellerItems[$seller->getKey()] = [];
                }

                $sellerItems[$seller->getKey()][] = $item;
            }
        }

        /** @var array<OrderItem> $items */
        foreach ($sellerItems as $items) {
            $seller = $items[0]->price->product->seller;

            if ($seller && $seller->email) {
                Mail::to($seller->email)->send(
                    new ProductSold($order, $seller, collect($items))
                );
            }
        }
    }
}
