<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Data\OrderData;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        #[CurrentUser]
        private readonly User $user,
    ) {
        //
    }

    public function __invoke(): Response
    {
        return Inertia::render('settings/orders', [
            'orders' => Inertia::defer(fn () => OrderData::collect(Order::query()
                ->whereBelongsTo($this->user)
                ->readyToView()
                ->with(['items.product'])
                ->latest()
                ->get()
                ->filter(fn (Order $order): bool => $order->status !== OrderStatus::Pending || filled($order->checkout_url))
                ->values())),
        ]);
    }
}
