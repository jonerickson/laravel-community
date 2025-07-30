<?php

declare(strict_types=1);

use App\Http\Controllers\BannedController;
use App\Http\Controllers\Blog\BlogController;
use App\Http\Controllers\Blog\CommentController;
use App\Http\Controllers\Comments\LikeController as CommentLikeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Forums\ForumController;
use App\Http\Controllers\Forums\ReadController as TopicReadController;
use App\Http\Controllers\Forums\TopicController;
use App\Http\Controllers\Policies\CategoryController as PolicyCategoryController;
use App\Http\Controllers\Policies\PolicyController;
use App\Http\Controllers\Posts\LikeController as PostLikeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::get('/forums', [ForumController::class, 'index'])->name('forums.index');
Route::get('/forums/{forum:slug}', [ForumController::class, 'show'])->name('forums.show');
Route::get('/forums/{forum:slug}/{topic:slug}', [TopicController::class, 'show'])->name('forums.topics.show');

Route::get('/policies', [PolicyCategoryController::class, 'index'])->name('policies.index');
Route::get('/policies/{category:slug}', [PolicyCategoryController::class, 'show'])->name('policies.categories.show');
Route::get('/policies/{category:slug}/{policy:slug}', [PolicyController::class, 'show'])->name('policies.show');

Route::get('/banned', BannedController::class)->name('banned')->middleware('auth');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::group(['prefix' => 'blog', 'as' => 'blog.'], function () {
        Route::get('/', [BlogController::class, 'index'])->name('index');
        Route::get('/{post:slug}', [BlogController::class, 'show'])->name('show');
        Route::post('/{post:slug}/comments', [CommentController::class, 'store'])->name('comments.store');
    });

    Route::group(['prefix' => 'forums', 'as' => 'forums.'], function () {
        Route::get('/{forum:slug}/create', [TopicController::class, 'create'])->name('topics.create');
        Route::post('/{forum:slug}/topics', [TopicController::class, 'store'])->name('topics.store');
        Route::post('/{forum:slug}/{topic:slug}/reply', [TopicController::class, 'reply'])->name('topics.reply');
        Route::post('/{forum:slug}/{topic:slug}/read', TopicReadController::class)->name('topics.read');
    });

    Route::post('/{post:slug}/like', PostLikeController::class)->name('posts.like');
    Route::post('/{comment}/like', CommentLikeController::class)->name('comments.like');
});

require __DIR__.'/settings.php';
require __DIR__.'/store.php';
require __DIR__.'/auth.php';
