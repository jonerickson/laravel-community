<?php

declare(strict_types=1);

use App\Http\Controllers\Policies\CategoryController;
use App\Http\Controllers\Policies\PolicyController;
use Illuminate\Support\Facades\Route;

Route::get('/policies', [CategoryController::class, 'index'])->name('policies.index');
Route::get('/policies/{category:slug}', [CategoryController::class, 'show'])->name('policies.categories.show');
Route::get('/policies/{category:slug}/{policy:slug}', [PolicyController::class, 'show'])->name('policies.show');
