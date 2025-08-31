<?php

declare(strict_types=1);

use App\Http\Controllers\Settings\AppearanceController;
use App\Http\Controllers\Settings\BillingController;
use App\Http\Controllers\Settings\InvoiceController;
use App\Http\Controllers\Settings\PaymentMethodController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::redirect('settings', '/settings/account')->name('settings');

    Route::get('settings/account', [ProfileController::class, 'edit'])->name('settings.profile.edit');
    Route::post('settings/account', [ProfileController::class, 'update'])->name('settings.profile.update');
    Route::delete('settings/account', [ProfileController::class, 'destroy'])->name('settings.profile.destroy');

    Route::get('settings/appearance', AppearanceController::class)->name('settings.appearance');
    Route::get('settings/billing', BillingController::class)->name('settings.billing');
    Route::get('settings/orders', InvoiceController::class)->name('settings.invoices');
    Route::get('settings/payment-methods', PaymentMethodController::class)->name('settings.payment-methods');
});
