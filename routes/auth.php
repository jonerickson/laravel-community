<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SetEmailPromptController;
use App\Http\Controllers\Auth\SetPasswordPromptController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\OAuth\CallbackController;
use App\Http\Controllers\OAuth\RedirectController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::redirect('register', 'onboarding')
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:register');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');

    Route::get('magic-link', [MagicLinkController::class, 'create'])
        ->name('magic-link.request');

    Route::post('magic-link', [MagicLinkController::class, 'store'])
        ->middleware('throttle:login')
        ->name('magic-link.send');

    Route::get('magic-link/login/{user:reference_id}', [MagicLinkController::class, 'index'])
        ->middleware(['signed', 'throttle:login'])
        ->name('magic-link.login');
});

Route::group(['prefix' => 'oauth'], function (): void {
    Route::get('redirect/{provider}', RedirectController::class)
        ->name('oauth.redirect');
    Route::get('callback/{provider}', CallbackController::class)
        ->name('oauth.callback');
});

Route::middleware('auth')->group(function (): void {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('set-email', [SetEmailPromptController::class, 'create'])
        ->name('set-email.notice');

    Route::post('set-email', [SetEmailPromptController::class, 'store'])
        ->name('set-email.verify');

    Route::get('set-password', [SetPasswordPromptController::class, 'create'])
        ->name('set-password.notice');

    Route::post('set-password', [SetPasswordPromptController::class, 'store'])
        ->name('set-password.verify');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
