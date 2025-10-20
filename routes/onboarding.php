<?php

declare(strict_types=1);

use App\Http\Controllers\Onboarding\OnboardingController;
use App\Http\Controllers\Onboarding\ProfileController;
use App\Http\Controllers\Onboarding\RegisterController;
use App\Http\Controllers\Onboarding\SubscriptionController;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Support\Facades\Route;

Route::withoutMiddleware([EnsureEmailIsVerified::class])->group(function (): void {
    Route::get('onboarding', [OnboardingController::class, 'index'])
        ->name('onboarding');

    Route::post('onboarding/register', RegisterController::class)
        ->name('onboarding.register')
        ->middleware(['throttle:register']);

    Route::middleware('auth')->group(function (): void {
        Route::put('onboarding', [OnboardingController::class, 'update'])
            ->name('onboarding.update');

        Route::post('onboarding/profile', ProfileController::class)
            ->name('onboarding.profile');

        Route::post('onboarding/subscribe', SubscriptionController::class)
            ->name('onboarding.subscribe');

        Route::post('onboarding', [OnboardingController::class, 'store'])
            ->name('onboarding.store');
    });
});
