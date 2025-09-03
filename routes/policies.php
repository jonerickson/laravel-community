<?php

declare(strict_types=1);

use App\Http\Controllers\Policies\CategoryController as PolicyCategoryController;
use App\Http\Controllers\Policies\PolicyController;
use Illuminate\Support\Facades\Route;

Route::get('/policies', (new PolicyCategoryController)->index(...))->name('policies.index');
Route::get('/policies/{category:slug}', (new PolicyCategoryController)->show(...))->name('policies.categories.show');
Route::get('/policies/{category:slug}/{policy:slug}', [PolicyController::class, 'show'])->name('policies.show');
