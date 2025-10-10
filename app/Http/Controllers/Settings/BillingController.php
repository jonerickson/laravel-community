<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateBillingRequest;
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
        ]);
    }

    public function update(UpdateBillingRequest $request): RedirectResponse
    {
        $this->user->update($request->validated());

        return back()->with('message', 'Your billing information was updated successfully.');
    }
}
