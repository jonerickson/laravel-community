<?php

declare(strict_types=1);

use App\Http\Controllers\Blog\BlogController;
use App\Http\Controllers\Blog\CommentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::group(['prefix' => 'blog', 'as' => 'blog.'], function () {
        Route::get('/', [BlogController::class, 'index'])->name('index');
        Route::get('/{post:slug}', [BlogController::class, 'show'])->name('show');
        Route::post('/{post:slug}/comments', [CommentController::class, 'store'])->name('comments.store');
        Route::patch('/{post:slug}/comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
        Route::delete('/{post:slug}/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    });
});
