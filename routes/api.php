<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\FingerprintController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\LockController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\PinController;
use App\Http\Controllers\Api\PublishController;
use App\Http\Controllers\Api\ReadController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\ShoppingCartController;
use App\Http\Controllers\Api\SupportTicketController;
use App\Http\Controllers\Api\TopicController;
use App\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => [EnsureFrontendRequestsAreStateful::class], 'as' => 'api.'], function (): void {
    Route::put('/cart', [ShoppingCartController::class, 'update'])->name('cart.update');
    Route::delete('/cart', [ShoppingCartController::class, 'destroy'])->name('cart.destroy');
    Route::post('/fingerprint', FingerprintController::class)->name('fingerprint');
    Route::get('/search', SearchController::class)->name('search');

    Route::group(['middleware' => ['auth:api', 'verified']], function (): void {
        Route::post('/checkout', CheckoutController::class)->name('checkout');
        Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');
        Route::delete('/forums/topics', [TopicController::class, 'destroy'])->name('forums.topics.destroy');
        Route::post('/like', LikeController::class)->name('like');
        Route::get('/payment-methods', PaymentMethodController::class)->name('payment-methods');
        Route::post('/pin', [PinController::class, 'store'])->name('pin.store');
        Route::delete('/pin', [PinController::class, 'destroy'])->name('pin.destroy');
        Route::post('/publish', [PublishController::class, 'store'])->name('publish.store');
        Route::delete('/publish', [PublishController::class, 'destroy'])->name('publish.destroy');
        Route::post('/lock', [LockController::class, 'store'])->name('lock.store');
        Route::delete('/lock', [LockController::class, 'destroy'])->name('lock.destroy');
        Route::post('/read', ReadController::class)->name('read');
        Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
        Route::post('/support-tickets', SupportTicketController::class)->name('support');

        Route::get('/me', fn () => auth()->guard('api')->user())->name('me');
    });
});
