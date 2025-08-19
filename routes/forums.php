<?php

declare(strict_types=1);

use App\Http\Controllers\Forums\ForumController;
use App\Http\Controllers\Forums\PostController;
use App\Http\Controllers\Forums\TopicController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::group(['prefix' => 'forums', 'as' => 'forums.'], function () {
        Route::get('/', [ForumController::class, 'index'])->name('index');
        Route::get('/{forum:slug}', [ForumController::class, 'show'])->name('show');
        Route::get('/{forum:slug}/create', [TopicController::class, 'create'])->name('topics.create');
        Route::get('/{forum:slug}/{topic:slug}', [TopicController::class, 'show'])->name('topics.show');
        Route::post('/{forum:slug}/topics', [TopicController::class, 'store'])->name('topics.store');
        Route::delete('/{forum:slug}/{topic:slug}', [TopicController::class, 'destroy'])->name('topics.destroy');
        Route::post('/{forum:slug}/{topic:slug}/reply', [PostController::class, 'store'])->name('posts.store');
        Route::patch('/{forum:slug}/{topic:slug}/posts/{post}', [PostController::class, 'update'])->name('posts.update');
        Route::delete('/{forum:slug}/{topic:slug}/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    });
});
