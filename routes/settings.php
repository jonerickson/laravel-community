<?php

declare(strict_types=1);

use App\Http\Controllers\Settings\AppearanceController;
use App\Http\Controllers\Settings\BillingController;
use App\Http\Controllers\Settings\DiscountController;
use App\Http\Controllers\Settings\DownloadsController;
use App\Http\Controllers\Settings\IntegrationsController;
use App\Http\Controllers\Settings\OrderController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\PaymentMethodController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth', 'email', 'password', 'verified', 'onboarded']], function (): void {
    Route::redirect('settings', '/settings/account')->name('settings');

    Route::get('settings/account', [ProfileController::class, 'edit'])->name('settings.profile.edit');
    Route::post('settings/account', [ProfileController::class, 'update'])->name('settings.profile.update');
    Route::delete('settings/account', [ProfileController::class, 'destroy'])->name('settings.profile.destroy');

    Route::put('settings/password', [PasswordController::class, 'update'])->name('settings.password.update');

    Route::get('settings/integrations', [IntegrationsController::class, 'index'])->name('settings.integrations.index');
    Route::delete('settings/integrations/{social}', [IntegrationsController::class, 'destroy'])->name('settings.integrations.destroy');

    Route::get('settings/appearance', AppearanceController::class)->name('settings.appearance');

    Route::get('settings/billing', BillingController::class)->name('settings.billing');
    Route::post('settings/billing', [BillingController::class, 'update'])->name('settings.billing.update');

    Route::get('settings/orders', OrderController::class)->name('settings.orders');
    Route::get('settings/discounts', DiscountController::class)->name('settings.discounts');
    Route::get('settings/downloads', DownloadsController::class)->name('settings.downloads');
    Route::get('settings/payment-methods', [PaymentMethodController::class, 'index'])->name('settings.payment-methods');
    Route::post('settings/payment-methods', [PaymentMethodController::class, 'store'])->name('settings.payment-methods.store');
    Route::patch('settings/payment-methods', [PaymentMethodController::class, 'update'])->name('settings.payment-methods.update');
    Route::delete('settings/payment-methods', [PaymentMethodController::class, 'destroy'])->name('settings.payment-methods.destroy');
});
