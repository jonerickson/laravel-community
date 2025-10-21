<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Pages\EditorController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'admin', 'as' => 'admin.', ['middleware' => ['auth']]], function (): void {
    Route::get('pages/{page}/editor', [EditorController::class, 'index'])->name('pages.index');
    Route::post('pages/{page}/editor', [EditorController::class, 'store'])->name('pages.store');
});
