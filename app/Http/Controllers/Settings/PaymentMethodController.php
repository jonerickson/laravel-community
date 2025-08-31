<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Cashier\PaymentMethod;

class PaymentMethodController extends Controller
{
    public function __invoke(): Response
    {
        $user = Auth::user();

        $paymentMethods = $user->paymentMethods()->map(fn (PaymentMethod $paymentMethod): array => [
            'id' => $paymentMethod->id,
            'type' => $paymentMethod->type,
            'brand' => $paymentMethod->card->brand ?? null,
            'last4' => $paymentMethod->card->last4 ?? null,
            'exp_month' => $paymentMethod->card->exp_month ?? null,
            'exp_year' => $paymentMethod->card->exp_year ?? null,
            'holder_name' => $paymentMethod->billing_details->name ?? null,
            'email' => $paymentMethod->billing_details->email ?? null,
            'is_default' => $user->defaultPaymentMethod()?->id === $paymentMethod->id,
        ]);

        return Inertia::render('settings/payment-methods', [
            'paymentMethods' => $paymentMethods,
        ]);
    }
}
