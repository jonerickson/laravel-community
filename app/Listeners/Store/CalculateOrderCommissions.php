<?php

declare(strict_types=1);

namespace App\Listeners\Store;

use App\Actions\Payouts\UpdateSellerBalanceAction;
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

                $item->update([
                    'commission_amount' => $commissionAmount,
                    'commission_recipient_id' => $product->seller_id,
                ]);

                $seller = User::find($product->seller_id);

                if ($seller) {
                    $updateBalanceAction = app(UpdateSellerBalanceAction::class);
                    $updateBalanceAction->execute(
                        seller: $seller,
                        amount: $commissionAmount,
                        reason: 'Commission from order '.$order->reference_id
                    );
                }

                $sellerId = $product->seller_id;
                if (! isset($sellerItems[$sellerId])) {
                    $sellerItems[$sellerId] = [];
                }

                $sellerItems[$sellerId][] = $item;
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
