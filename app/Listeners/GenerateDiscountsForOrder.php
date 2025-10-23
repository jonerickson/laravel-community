<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\DiscountType;
use App\Events\OrderSucceeded;
use App\Mail\Store\GiftCardReceived;
use App\Mail\Store\PromoCodeReceived;
use App\Models\Discount;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class GenerateDiscountsForOrder implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    public function handle(OrderSucceeded $event): void
    {
        $order = $event->order;

        $orderItems = $order->items()
            ->with(['product', 'price'])
            ->get();

        foreach ($orderItems as $item) {
            if (! $item->product) {
                continue;
            }

            $templateDiscount = Discount::query()
                ->whereBelongsTo($item->product, 'product')
                ->whereNull('user_id')
                ->first();

            if (! $templateDiscount) {
                continue;
            }

            for ($i = 0; $i < $item->quantity; $i++) {
                $discount = $templateDiscount->replicate([
                    'id',
                    'times_used',
                    'activated_at',
                    'product_id',
                    'created_at',
                    'updated_at',
                ]);

                $discount->user_id = $order->user_id;
                $discount->recipient_email = $order->user->email;
                $discount->code = $discount->generateCode();
                $discount->times_used = 0;
                $discount->activated_at = null;
                $discount->save();

                if ($order->user) {
                    $mailable = match ($discount->type) {
                        DiscountType::GiftCard => new GiftCardReceived($discount, $order->user),
                        DiscountType::PromoCode, DiscountType::Manual => new PromoCodeReceived($discount, $order->user),
                    };

                    Mail::to($discount->recipient_email)->send($mailable);
                }
            }
        }
    }
}
