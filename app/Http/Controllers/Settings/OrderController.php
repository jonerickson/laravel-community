<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

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
            'orders' => Order::query()
                ->whereBelongsTo($user)
                ->readyToView()
                ->with(['items.product'])
                ->latest()
                ->get(),
        ]);
    }
}
