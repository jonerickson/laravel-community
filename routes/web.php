<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('home'))->name('home');

Route::group(['middleware' => 'auth', 'verified'], function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

require __DIR__.'/auth.php';
require __DIR__.'/blog.php';
require __DIR__.'/forums.php';
require __DIR__.'/policies.php';
require __DIR__.'/settings.php';
require __DIR__.'/store.php';
require __DIR__.'/support.php';
