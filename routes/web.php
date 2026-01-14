<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::group(['domain' => parse_url((string) config('app.url'), PHP_URL_HOST)], function (): void {
    Route::get('/', HomeController::class)->name('home');
    Route::get('maintenance', MaintenanceController::class)->name('maintenance');
    Route::get('search', SearchController::class)->name('search');
    Route::get('users/{user:reference_id}', [UserController::class, 'show'])->name('users.show');

    Route::group(['middleware' => ['auth', 'email', 'password', 'verified', 'onboarded']], function (): void {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::impersonate();
    });

    require __DIR__.'/admin.php';
    require __DIR__.'/auth.php';
    require __DIR__.'/blog.php';
    require __DIR__.'/cashier.php';
    require __DIR__.'/forums.php';
    require __DIR__.'/knowledge-base.php';
    require __DIR__.'/onboarding.php';
    require __DIR__.'/pages.php';
    require __DIR__.'/policies.php';
    require __DIR__.'/settings.php';
    require __DIR__.'/store.php';
    require __DIR__.'/support.php';
});
