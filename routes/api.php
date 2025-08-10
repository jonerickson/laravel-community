<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\FingerprintController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\ReadController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\ShoppingCartController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth:api', 'as' => 'api.'], function () {
    Route::post('/cart', [ShoppingCartController::class, 'store'])->name('cart.store');
    Route::put('/cart', [ShoppingCartController::class, 'update'])->name('cart.update');
    Route::delete('/cart', [ShoppingCartController::class, 'destroy'])->name('cart.destroy');
    Route::post('/checkout', CheckoutController::class)->name('checkout');
    Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::post('/fingerprint', FingerprintController::class)->name('fingerprint');
    Route::post('/like', LikeController::class)->name('like');
    Route::get('/payment-methods', [PaymentMethodController::class, 'create'])->name('payment-methods.create');
    Route::patch('/payment-methods', [PaymentMethodController::class, 'update'])->name('payment-methods.update');
    Route::delete('/payment-methods', [PaymentMethodController::class, 'destroy'])->name('payment-methods.destroy');
    Route::post('/read', ReadController::class)->name('read');
    Route::get('/search', SearchController::class)->name('search');
});
