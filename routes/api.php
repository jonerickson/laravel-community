<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\FingerprintController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\ReadController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\ShoppingCartController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth:api', 'as' => 'api.'], function () {
    Route::apiResource('/cart', ShoppingCartController::class)->only(['store', 'update', 'destroy']);
    Route::post('/checkout', CheckoutController::class)->name('checkout');
    Route::apiResource('/comments', CommentController::class)->only(['store']);
    Route::post('/fingerprint', FingerprintController::class)->name('fingerprint');
    Route::post('/like', LikeController::class)->name('like');
    Route::post('/read', ReadController::class)->name('read');
    Route::get('/search', SearchController::class)->name('search');
});
