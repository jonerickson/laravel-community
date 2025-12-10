<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Data\CustomerData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\DestroyPaymentMethodRequest;
use App\Http\Requests\Settings\StorePaymentMethodRequest;
use App\Http\Requests\Settings\UpdatePaymentMethodRequest;
use App\Managers\PaymentManager;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PaymentMethodController extends Controller
{
    public function __construct(
        #[CurrentUser]
        private readonly User $user,
        private readonly PaymentManager $paymentManager
    ) {
        //
    }

    public function index(): Response
    {
        return Inertia::render('settings/payment-methods', [
            'paymentMethods' => $this->paymentManager->listPaymentMethods($this->user),
        ]);
    }

    public function store(StorePaymentMethodRequest $request): RedirectResponse
    {
        $result = true;
        if (! $this->paymentManager->getCustomer($this->user) instanceof CustomerData) {
            $result = $this->paymentManager->createCustomer($this->user);
        }

        if (! $result) {
            return back()->with([
                'message' => 'The payment method creation failed. Please try again later.',
                'messageVariant' => 'error',
            ]);
        }

        $paymentMethod = $this->paymentManager->createPaymentMethod(
            user: $this->user,
            paymentMethodId: $request->validated('method'),
        );

        if (blank($paymentMethod)) {
            return back()->with([
                'message' => 'The payment method creation failed. Please try again later.',
                'messageVariant' => 'error',
            ]);
        }

        $this->user->updateDefaultPaymentMethod($paymentMethod->id);
        $this->user->updateDefaultPaymentMethodFromStripe();

        return redirect()->route('settings.payment-methods')->with([
            'message' => 'Your payment method was successfully added.',
            'messageVariant' => 'success',
        ]);
    }

    public function update(UpdatePaymentMethodRequest $request): RedirectResponse
    {
        $updated = $this->paymentManager->updatePaymentMethod(
            user: $this->user,
            paymentMethodId: $request->validated('method'),
            isDefault: $request->validated('is_default'),
        );

        if (blank($updated)) {
            return back()->with([
                'message' => 'The payment method was not found. Please try again later.',
                'messageVariant' => 'error',
            ]);
        }

        $this->user->updateDefaultPaymentMethodFromStripe();

        return back()->with([
            'message' => 'The payment method was updated successfully.',
            'messageVariant' => 'success',
        ]);
    }

    public function destroy(DestroyPaymentMethodRequest $request): RedirectResponse
    {
        $deleted = $this->paymentManager->deletePaymentMethod(
            user: $this->user,
            paymentMethodId: $request->validated('method'),
        );

        if (blank($deleted)) {
            return back()->with([
                'message' => 'The payment method was not found. Please try again later.',
                'messageVariant' => 'error',
            ]);
        }

        $this->user->updateDefaultPaymentMethodFromStripe();

        return back()->with([
            'message' => 'The payment method was successfully removed.',
            'messageVariant' => 'success',
        ]);
    }
}
