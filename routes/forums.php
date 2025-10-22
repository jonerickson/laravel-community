<?php

declare(strict_types=1);

use App\Http\Controllers\Forums\CategoryController;
use App\Http\Controllers\Forums\ForumController;
use App\Http\Controllers\Forums\PostController;
use App\Http\Controllers\Forums\TopicController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'forums', 'as' => 'forums.'], function (): void {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::get('/categories/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');
    Route::get('/{forum:slug}', [ForumController::class, 'show'])->name('show');

    Route::group(['middleware' => ['auth', 'verified']], function (): void {
        Route::post('/{forum:slug}/topics', [TopicController::class, 'store'])->middleware('throttle:post')->name('topics.store');
        Route::get('/{forum:slug}/topics/create', [TopicController::class, 'create'])->name('topics.create');
        Route::delete('/{forum:slug}/topics/{topic:slug}', [TopicController::class, 'destroy'])->name('topics.destroy');
        Route::get('/{forum:slug}/topics/{topic:slug}/posts/{post:slug}/edit', [PostController::class, 'edit'])->name('posts.edit');
        Route::patch('/{forum:slug}/topics/{topic:slug}/posts/{post:slug}', [PostController::class, 'update'])->name('posts.update');
        Route::delete('/{forum:slug}/topics/{topic:slug}/posts/{post:slug}', [PostController::class, 'destroy'])->name('posts.destroy');
        Route::post('/{forum:slug}/topics/{topic:slug}/reply', [PostController::class, 'store'])->middleware('throttle:post')->name('posts.store');
    });

    Route::get('/{forum:slug}/topics/{topic:slug}', [TopicController::class, 'show'])->name('topics.show');
});
