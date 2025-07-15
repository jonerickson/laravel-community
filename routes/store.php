<?php

declare(strict_types=1);

use App\Http\Controllers\Store\CategoryController;
use App\Http\Controllers\Store\ProductController;
use App\Http\Controllers\Store\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('store', CategoryController::class)->name('store.categories');
    Route::get('store/products/{product}', ProductController::class)->name('store.products.view');
    Route::get('subscriptions', SubscriptionController::class)->name('store.subscriptions');
});
