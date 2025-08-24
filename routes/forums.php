<?php

declare(strict_types=1);

use App\Http\Controllers\Forums\ForumController;
use App\Http\Controllers\Forums\PostController;
use App\Http\Controllers\Forums\TopicController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'forums', 'as' => 'forums.'], function () {
    Route::get('/', [ForumController::class, 'index'])->name('index');
    Route::get('/{forum:slug}', [ForumController::class, 'show'])->name('show');

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::post('/{forum:slug}/topics', [TopicController::class, 'store'])->name('topics.store');
        Route::get('/{forum:slug}/topics/create', [TopicController::class, 'create'])->name('topics.create');
        Route::delete('/{forum:slug}/topics/{topic:slug}', [TopicController::class, 'destroy'])->name('topics.destroy');
        Route::patch('/{forum:slug}/topics/{topic:slug}/posts/{post}', [PostController::class, 'update'])->name('posts.update');
        Route::delete('/{forum:slug}/topics/{topic:slug}/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
        Route::post('/{forum:slug}/topics/{topic:slug}/reply', [PostController::class, 'store'])->name('posts.store');
    });

    Route::get('/{forum:slug}/topics/{topic:slug}', [TopicController::class, 'show'])->name('topics.show');
});
