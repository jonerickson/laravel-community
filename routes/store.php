<?php

declare(strict_types=1);

use App\Http\Controllers\Store\CategoryController;
use App\Http\Controllers\Store\ProductController;
use App\Http\Controllers\Store\ShoppingCartController;
use App\Http\Controllers\Store\StoreController;
use App\Http\Controllers\Store\SubscriptionsController;
use Illuminate\Support\Facades\Route;

Route::group(['as' => 'store.', 'prefix' => 'store'], function (): void {
    Route::get('/', StoreController::class)->name('index');
    Route::get('categories/{category:slug}', CategoryController::class)->name('categories.show');
    Route::get('products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
    Route::post('products/{product:slug}', [ProductController::class, 'store'])->name('products.store');
    Route::get('subscriptions', [SubscriptionsController::class, 'index'])->name('subscriptions');
    Route::get('cart', [ShoppingCartController::class, 'index'])->name('cart.index');
    Route::delete('cart', [ShoppingCartController::class, 'destroy'])->name('cart.destroy');

    Route::group(['middleware' => ['auth', 'verified']], function (): void {
        Route::redirect('checkout/success', '/store/cart')->name('checkout.success');
        Route::redirect('checkout/cancel', '/store/cart')->name('checkout.cancel');
    });
});
