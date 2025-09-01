<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateBillingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('settings/billing', [
            'user' => Auth::user()->only([
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
        $user = Auth::user();
        $user->update($request->validated());

        return back()->with('message', 'Billing information updated successfully!');
    }
}
