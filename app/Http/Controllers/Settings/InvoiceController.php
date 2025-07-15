<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('settings/invoices', [
            'invoices' => [
                [
                    'id' => 1,
                    'date' => now(),
                    'amount' => 100,
                    'status' => 'open',
                    'invoice_url' => '',
                ],
                [
                    'id' => 2,
                    'date' => now()->addDays(30),
                    'amount' => 150,
                    'status' => 'paid',
                    'invoice_url' => '',
                ],
            ],
        ]);
    }
}
