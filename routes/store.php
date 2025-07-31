<?php

declare(strict_types=1);

use App\Http\Controllers\Store\CategoryController;
use App\Http\Controllers\Store\ProductController;
use App\Http\Controllers\Store\ShoppingCartController;
use App\Http\Controllers\Store\StoreController;
use App\Http\Controllers\Store\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth', 'verified'], 'as' => 'store.', 'prefix' => 'store'], function () {
    Route::get('/', StoreController::class)->name('index');
    Route::get('categories/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');
    Route::get('products/{product:slug}', ProductController::class)->name('products.show');
    Route::get('subscriptions', SubscriptionController::class)->name('subscriptions');

    Route::get('cart', [ShoppingCartController::class, 'index'])->name('cart');
    Route::post('cart', [ShoppingCartController::class, 'store'])->name('cart.store');
    Route::put('cart/{product}', [ShoppingCartController::class, 'update'])->name('cart.update');
    Route::delete('cart/{product}', [ShoppingCartController::class, 'delete'])->name('cart.delete');
    Route::delete('cart', [ShoppingCartController::class, 'clear'])->name('cart.clear');
    Route::post('cart/checkout', [ShoppingCartController::class, 'checkout'])->name('cart.checkout');

    Route::redirect('checkout/success', '/store/cart')->name('checkout.success');
    Route::redirect('checkout/cancel', '/store/cart')->name('checkout.cancel');
});
