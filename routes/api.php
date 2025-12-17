<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Frontend\ApproveController;
use App\Http\Controllers\Api\Frontend\CheckoutController;
use App\Http\Controllers\Api\Frontend\DiscountController;
use App\Http\Controllers\Api\Frontend\FileController;
use App\Http\Controllers\Api\Frontend\FingerprintController;
use App\Http\Controllers\Api\Frontend\FollowController;
use App\Http\Controllers\Api\Frontend\LikeController;
use App\Http\Controllers\Api\Frontend\LockController;
use App\Http\Controllers\Api\Frontend\PaymentMethodController;
use App\Http\Controllers\Api\Frontend\PinController;
use App\Http\Controllers\Api\Frontend\Profile\SyncController;
use App\Http\Controllers\Api\Frontend\PublishController;
use App\Http\Controllers\Api\Frontend\ReadController;
use App\Http\Controllers\Api\Frontend\ReportController;
use App\Http\Controllers\Api\Frontend\ReviewController;
use App\Http\Controllers\Api\Frontend\SearchController;
use App\Http\Controllers\Api\Frontend\ShoppingCartController;
use App\Http\Controllers\Api\Frontend\TopicController;
use App\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Illuminate\Support\Facades\Route;

Route::group(['domain' => config('app.url'), 'middleware' => [EnsureFrontendRequestsAreStateful::class], 'as' => 'api.'], function (): void {
    Route::post('/cart', [ShoppingCartController::class, 'store'])->name('cart.store');
    Route::put('/cart', [ShoppingCartController::class, 'update'])->name('cart.update');
    Route::delete('/cart', [ShoppingCartController::class, 'destroy'])->name('cart.destroy');
    Route::post('/discount/validate', [DiscountController::class, 'store'])->name('discount.store');
    Route::post('/discount/remove', [DiscountController::class, 'destroy'])->name('discount.destroy');
    Route::post('/fingerprint', FingerprintController::class)->name('fingerprint');
    Route::get('/search', SearchController::class)->name('search');

    Route::group(['middleware' => ['auth:api', 'verified']], function (): void {
        Route::post('/approve', [ApproveController::class, 'store'])->name('approve.store');
        Route::delete('/approve', [ApproveController::class, 'destroy'])->name('approve.destroy');
        Route::post('/checkout', CheckoutController::class)->name('checkout');
        Route::post('/comments', [ReviewController::class, 'store'])->middleware('throttle:comment')->name('comments.store');
        Route::post('/file', [FileController::class, 'store'])->name('file.store');
        Route::delete('/file', [FileController::class, 'destroy'])->name('file.destroy');
        Route::post('/follow', [FollowController::class, 'store'])->name('follow.store');
        Route::delete('/follow/', [FollowController::class, 'destroy'])->name('follow.destroy');
        Route::delete('/forums/topics', [TopicController::class, 'destroy'])->name('forums.topics.destroy');
        Route::put('/forums/topics/{topic:slug}', [TopicController::class, 'update'])->name('forums.topics.update');
        Route::post('/like', LikeController::class)->name('like');
        Route::get('/payment-methods', PaymentMethodController::class)->name('payment-methods');
        Route::post('/pin', [PinController::class, 'store'])->name('pin.store');
        Route::delete('/pin', [PinController::class, 'destroy'])->name('pin.destroy');
        Route::post('/profile/sync', SyncController::class)->name('profile.sync');
        Route::post('/publish', [PublishController::class, 'store'])->name('publish.store');
        Route::delete('/publish', [PublishController::class, 'destroy'])->name('publish.destroy');
        Route::post('/lock', [LockController::class, 'store'])->name('lock.store');
        Route::delete('/lock', [LockController::class, 'destroy'])->name('lock.destroy');
        Route::post('/read', ReadController::class)->name('read');
        Route::post('/reports', [ReportController::class, 'store'])->middleware('throttle:report')->name('reports.store');
    });
});
