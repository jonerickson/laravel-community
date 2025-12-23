<?php

declare(strict_types=1);

namespace App\Actions\Commissions;

use App\Actions\Action;
use App\Enums\CommissionStatus;
use App\Models\Commission;
use App\Models\Order;
use App\Models\User;

class RecordCommissionAction extends Action
{
    public function __construct(
        protected User $seller,
        protected Order $order,
        protected float $amount,
        protected CommissionStatus $status = CommissionStatus::Pending
    ) {
        //
    }

    public function __invoke(): Commission
    {
        return Commission::create([
            'seller_id' => $this->seller->id,
            'order_id' => $this->order->id,
            'amount' => $this->amount,
            'status' => $this->status,
        ]);
    }
}
