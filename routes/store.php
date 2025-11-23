<?php

declare(strict_types=1);

use App\Http\Controllers\Store\CategoryController;
use App\Http\Controllers\Store\CheckoutCancelController;
use App\Http\Controllers\Store\CheckoutSuccessController;
use App\Http\Controllers\Store\ProductController;
use App\Http\Controllers\Store\ReviewController;
use App\Http\Controllers\Store\ShoppingCartController;
use App\Http\Controllers\Store\StoreController;
use App\Http\Controllers\Store\SubscriptionsController;
use Illuminate\Support\Facades\Route;

Route::group(['as' => 'store.', 'prefix' => 'store'], function (): void {
    Route::get('/', StoreController::class)->name('index');
    Route::get('cart', [ShoppingCartController::class, 'index'])->name('cart.index');
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('categories/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');
    Route::get('products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
    Route::get('subscriptions', [SubscriptionsController::class, 'index'])->name('subscriptions');
    Route::get('subscriptions/{subscription:reference_id}/reviews', [ReviewController::class, 'index'])->name('subscriptions.reviews');

    Route::group(['middleware' => ['auth']], function (): void {
        Route::delete('cart', [ShoppingCartController::class, 'destroy'])->name('cart.destroy');
        Route::post('products/{product:slug}', [ProductController::class, 'store'])->name('products.store');
        Route::post('subscriptions', [SubscriptionsController::class, 'store'])->name('subscriptions.store');
        Route::put('subscriptions', [SubscriptionsController::class, 'update'])->name('subscriptions.update');
        Route::delete('subscriptions', [SubscriptionsController::class, 'destroy'])->name('subscriptions.destroy');
        Route::post('subscriptions/{subscription:reference_id}/reviews', [ReviewController::class, 'store'])->middleware('throttle:comment')->name('subscriptions.reviews.store');
    });

    Route::group(['middleware' => ['auth', 'verified', 'signed']], function (): void {
        Route::get('checkout/success/{order:reference_id}', CheckoutSuccessController::class)->name('checkout.success');
        Route::get('checkout/cancel/{order:reference_id}', CheckoutCancelController::class)->name('checkout.cancel');
    });
});
