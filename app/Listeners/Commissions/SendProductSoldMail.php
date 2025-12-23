<?php

declare(strict_types=1);

namespace App\Listeners\Commissions;

use App\Events\CommissionCreated;
use App\Mail\Marketplace\ProductSold;
use Illuminate\Support\Facades\Mail;

class SendProductSoldMail
{
    public function handle(CommissionCreated $event): void
    {
        if ((! $seller = $event->commission->seller) || (! $order = $event->commission->order)) {
            return;
        }

        Mail::to($seller->email)->send(new ProductSold(
            order: $order,
            seller: $seller,
        ));
    }
}
