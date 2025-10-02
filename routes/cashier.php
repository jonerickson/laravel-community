<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::group([
    'as' => 'cashier.',
    'domain' => config('app.url'),
    'prefix' => config('cashier.path'),
    'namespace' => 'Laravel\Cashier\Http\Controllers',
], function (): void {
    require __DIR__.'/../vendor/laravel/cashier/routes/web.php';
});
