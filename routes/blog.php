<?php

declare(strict_types=1);

use App\Http\Controllers\Blog\BlogController;
use App\Http\Controllers\Blog\CommentController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'blog', 'as' => 'blog.'], function (): void {
    Route::get('/', [BlogController::class, 'index'])->name('index');
    Route::get('/{post:slug}', [BlogController::class, 'show'])->name('show');

    Route::group(['middleware' => ['auth', 'verified']], function (): void {
        Route::post('/{post:slug}/comments', [CommentController::class, 'store'])->middleware('throttle:comment')->name('comments.store');
        Route::patch('/{post:slug}/comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
        Route::delete('/{post:slug}/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    });
});
