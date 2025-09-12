<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Managers\PaymentManager;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function __construct(private readonly PaymentManager $paymentManager)
    {
        //
    }

    public function __invoke(): Response
    {
        $user = Auth::user();

        return Inertia::render('settings/invoices', [
            'invoices' => $this->paymentManager->getInvoices($user),
        ]);
    }
}
