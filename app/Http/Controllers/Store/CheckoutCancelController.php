<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Managers\PaymentManager;
use App\Models\Order;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckoutCancelController extends Controller
{
    public function __construct(
        private readonly Request $request,
        private readonly PaymentManager $paymentManager,
        private readonly InventoryService $inventoryService,
    ) {
        //
    }

    public function __invoke(Order $order)
    {
        $this->paymentManager->processCheckoutCancel(
            request: $this->request,
            order: $order
        );

        try {
            $this->inventoryService->releaseReservations($order);
        } catch (Throwable $throwable) {
            Log::error('Error releasing product reservations', [
                'message' => $throwable->getMessage(),
            ]);
        }

        if ($redirect = $this->request->query('redirect')) {
            return redirect(urldecode($redirect));
        }

        return to_route('store.cart.index')
            ->with('message', 'The order was successfully cancelled.');
    }
}
