<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Data\OrderData;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __invoke(): Response
    {
        $user = Auth::user();

        return Inertia::render('settings/orders', [
            'orders' => OrderData::collect(Order::query()
                ->whereBelongsTo($user)
                ->readyToView()
                ->with(['items.product'])
                ->latest()
                ->get()
                ->filter(fn (Order $order) => ! ($order->status === OrderStatus::Pending) || filled($order->checkout_url))
                ->values()),
        ]);
    }
}
