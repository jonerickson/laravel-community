<?php

declare(strict_types=1);

use App\Http\Controllers\Api\FingerprintController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth:api'], function () {
    Route::post('/fingerprint', FingerprintController::class)->name('fingerprint');
    Route::get('/search', SearchController::class)->name('search');
    Route::post('/like', LikeController::class)->name('like');
});
