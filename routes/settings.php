<?php

use App\Http\Controllers\Settings\BillingController;
use App\Http\Controllers\Settings\InvoiceController;
use App\Http\Controllers\Settings\PaymentMethodController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;

Route::middleware([
    'auth',
    ValidateSessionWithWorkOS::class,
])->group(function () {
    Route::redirect('settings', '/settings/account');

    Route::get('settings/account', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/account', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/account', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/billing', BillingController::class)->name('profile.billing');
    Route::get('settings/orders', InvoiceController::class)->name('profile.invoices');
    Route::get('settings/payment-methods', PaymentMethodController::class)->name('profile.payment-methods');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance');
});
