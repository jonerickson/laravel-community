<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\DestroyPaymentMethodRequest;
use App\Http\Requests\Settings\StorePaymentMethodRequest;
use App\Http\Requests\Settings\UpdatePaymentMethodRequest;
use App\Managers\PaymentManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PaymentMethodController extends Controller
{
    public function __construct(private readonly PaymentManager $paymentManager)
    {
        //
    }

    public function index(): Response
    {
        $user = Auth::user();

        return Inertia::render('settings/payment-methods', [
            'paymentMethods' => $this->paymentManager->listPaymentMethods($user),
        ]);
    }

    public function store(StorePaymentMethodRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = Auth::user();

        $created = $this->paymentManager->createPaymentMethod(
            user: $user,
            paymentMethodId: $validated['method']
        );

        if (blank($created)) {
            return back()->with([
                'message' => 'The payment method creation failed. Please try again later.',
                'messageVariant' => 'error',
            ]);
        }

        return redirect()->route('settings.payment-methods')->with([
            'message' => 'Your payment method was successfully added.',
            'messageVariant' => 'success',
        ]);
    }

    public function update(UpdatePaymentMethodRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = Auth::user();

        $updated = $this->paymentManager->updatePaymentMethod(
            user: $user,
            paymentMethodId: $validated['method'],
            isDefault: $validated['is_default']
        );

        if (blank($updated)) {
            return back()->with([
                'message' => 'The payment method was not found. Please try again later.',
                'messageVariant' => 'error',
            ]);
        }

        return back()->with([
            'message' => 'The payment method was updated successfully.',
            'messageVariant' => 'success',
        ]);
    }

    public function destroy(DestroyPaymentMethodRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = Auth::user();

        $deleted = $this->paymentManager->deletePaymentMethod(
            user: $user,
            paymentMethodId: $validated['method']
        );

        if (blank($deleted)) {
            return back()->with([
                'message' => 'The payment method was not found. Please try again later.',
                'messageVariant' => 'error',
            ]);
        }

        return back()->with([
            'message' => 'The payment method was successfully removed.',
            'messageVariant' => 'success',
        ]);
    }
}
