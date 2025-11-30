<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Data\OrderData;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Collection;
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
            'orders' => Inertia::defer(fn (): Collection => OrderData::collect(Order::query()
                ->whereBelongsTo($this->user)
                ->readyToView()
                ->with(['items.price.product'])
                ->latest()
                ->get()
                ->values())),
        ]);
    }
}
