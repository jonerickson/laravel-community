<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Data\CustomerData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateBillingRequest;
use App\Managers\PaymentManager;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function __construct(
        #[CurrentUser]
        private readonly User $user,
        private readonly PaymentManager $paymentManager,
    ) {
        //
    }

    public function __invoke(): Response
    {
        return Inertia::render('settings/billing', [
            'user' => $this->user->only([
                'billing_address',
                'billing_address_line_2',
                'billing_city',
                'billing_state',
                'billing_postal_code',
                'billing_country',
                'vat_id',
                'extra_billing_information',
            ]),
            'portalUrl' => Inertia::defer(fn (): ?string => $this->paymentManager->getBillingPortalUrl($this->user)),
        ]);
    }

    public function update(UpdateBillingRequest $request): RedirectResponse
    {
        $this->user->update($request->validated());

        $result = true;
        if (! $this->paymentManager->getCustomer($this->user) instanceof CustomerData) {
            $result = $this->paymentManager->createCustomer($this->user);
        }

        if (! $result) {
            return back()->with([
                'message' => 'We were unable to sync your billing data. Please try again.',
                'messageVariant' => 'error',
            ]);
        }

        $this->paymentManager->syncCustomerInformation($this->user);

        return back()->with('message', 'Your billing information was updated successfully.');
    }
}
