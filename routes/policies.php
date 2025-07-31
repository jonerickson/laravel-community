<?php

declare(strict_types=1);

use App\Http\Controllers\Policies\CategoryController as PolicyCategoryController;
use App\Http\Controllers\Policies\PolicyController;
use Illuminate\Support\Facades\Route;

Route::get('/policies', [PolicyCategoryController::class, 'index'])->name('policies.index');
Route::get('/policies/{category:slug}', [PolicyCategoryController::class, 'show'])->name('policies.categories.show');
Route::get('/policies/{category:slug}/{policy:slug}', [PolicyController::class, 'show'])->name('policies.show');
