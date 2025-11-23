<?php

declare(strict_types=1);

use App\Http\Middleware\EnsurePassportResponseWorksWithInertia;
use Illuminate\Support\Facades\Route;

Route::group([
    'as' => 'passport.',
    'domain' => config('app.url'),
    'prefix' => config('passport.path', 'oauth'),
    'namespace' => 'Laravel\Passport\Http\Controllers',
    'middleware' => [EnsurePassportResponseWorksWithInertia::class],
], function (): void {
    require __DIR__.'/../vendor/laravel/passport/routes/web.php';
});
