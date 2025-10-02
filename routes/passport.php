<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::group([
    'as' => 'passport.',
    'domain' => config('app.url'),
    'prefix' => config('passport.path', 'oauth'),
    'namespace' => 'Laravel\Passport\Http\Controllers',
], function (): void {
    require __DIR__.'/../vendor/laravel/passport/routes/web.php';
});
