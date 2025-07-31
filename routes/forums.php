<?php

declare(strict_types=1);

use App\Http\Controllers\Forums\ForumController;
use App\Http\Controllers\Forums\ReadController as TopicReadController;
use App\Http\Controllers\Forums\TopicController;
use Illuminate\Support\Facades\Route;

Route::get('/forums', [ForumController::class, 'index'])->name('forums.index');
Route::get('/forums/{forum:slug}', [ForumController::class, 'show'])->name('forums.show');
Route::get('/forums/{forum:slug}/{topic:slug}', [TopicController::class, 'show'])->name('forums.topics.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::group(['prefix' => 'forums', 'as' => 'forums.'], function () {
        Route::get('/{forum:slug}/create', [TopicController::class, 'create'])->name('topics.create');
        Route::post('/{forum:slug}/topics', [TopicController::class, 'store'])->name('topics.store');
        Route::post('/{forum:slug}/{topic:slug}/reply', [TopicController::class, 'reply'])->name('topics.reply');
        Route::post('/{forum:slug}/{topic:slug}/read', TopicReadController::class)->name('topics.read');
    });
});
