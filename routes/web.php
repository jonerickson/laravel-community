<?php

declare(strict_types=1);

use App\Http\Controllers\Blog\IndexController;
use App\Http\Controllers\Blog\ShowController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::group(['prefix' => 'blog', 'as' => 'blog.'], function () {
        Route::get('/', IndexController::class)->name('index');
        Route::get('/{post}', ShowController::class)->name('show');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/store.php';
require __DIR__.'/auth.php';
