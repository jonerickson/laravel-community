<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Managers\PaymentManager;
use App\Models\Order;
use Illuminate\Http\Request;

class CheckoutSuccessController extends Controller
{
    public function __construct(
        private readonly Request $request,
        private readonly PaymentManager $paymentManager,
    ) {
        //
    }

    public function __invoke(Order $order)
    {
        $this->paymentManager->processCheckoutSuccess(
            request: $this->request,
            order: $order
        );

        if ($redirect = $this->request->query('redirect')) {
            return redirect(urldecode($redirect));
        }

        return to_route('settings.orders')
            ->with('message', 'The order was successfully processed.');
    }
}
