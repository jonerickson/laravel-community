<?php

declare(strict_types=1);

use App\Http\Controllers\BannedController;
use App\Http\Controllers\Comments\LikeController as CommentLikeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Posts\LikeController as PostLikeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::get('/banned', BannedController::class)->name('banned')->middleware('auth');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::post('/{post:slug}/like', PostLikeController::class)->name('posts.like');
    Route::post('/{comment}/like', CommentLikeController::class)->name('comments.like');
});

require __DIR__.'/auth.php';
require __DIR__.'/blog.php';
require __DIR__.'/forums.php';
require __DIR__.'/policies.php';
require __DIR__.'/settings.php';
require __DIR__.'/store.php';
