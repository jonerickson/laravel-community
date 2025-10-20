<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::group(['domain' => config('app.url')], function (): void {
    Route::get('/', HomeController::class)->name('home');

    Route::group(['middleware' => ['auth', 'email', 'verified', 'onboarded']], function (): void {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

    require __DIR__.'/auth.php';
    require __DIR__.'/blog.php';
    require __DIR__.'/cashier.php';
    require __DIR__.'/forums.php';
    require __DIR__.'/onboarding.php';
    require __DIR__.'/passport.php';
    require __DIR__.'/policies.php';
    require __DIR__.'/settings.php';
    require __DIR__.'/store.php';
    require __DIR__.'/support.php';
});
