<?php

declare(strict_types=1);

use App\Http\Controllers\Blog\IndexController;
use App\Http\Controllers\Blog\ShowController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Forums\ForumController;
use App\Http\Controllers\Forums\TopicController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::get('/forums', [ForumController::class, 'index'])->name('forums.index');
Route::get('/forums/{forum:slug}', [ForumController::class, 'show'])->name('forums.show');
Route::get('/forums/{forum:slug}/{topic:slug}', [TopicController::class, 'show'])->name('forums.topics.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::group(['prefix' => 'blog', 'as' => 'blog.'], function () {
        Route::get('/', IndexController::class)->name('index');
        Route::get('/{post:slug}', ShowController::class)->name('show');
    });

    Route::group(['prefix' => 'forums', 'as' => 'forums.'], function () {
        Route::get('/{forum:slug}/create', [TopicController::class, 'create'])->name('topics.create');
        Route::post('/{forum:slug}/topics', [TopicController::class, 'store'])->name('topics.store');
        Route::post('/{forum:slug}/{topic:slug}/reply', [TopicController::class, 'reply'])->name('topics.reply');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/store.php';
require __DIR__.'/auth.php';
