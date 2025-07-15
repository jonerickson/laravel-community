<?php

declare(strict_types=1);

use App\Http\Controllers\News\IndexController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::group(['prefix' => 'news', 'as' => 'news.'], function () {
        Route::get('/', IndexController::class)->name('index');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/store.php';
require __DIR__.'/auth.php';
