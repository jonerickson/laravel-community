<?php

declare(strict_types=1);

use App\Exceptions\BannedException;
use App\Http\Middleware\CheckBannedUser;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Passport\Http\Middleware\CreateFreshApiToken;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'stripe/*',
        ]);

        $middleware->encryptCookies(except: [
            'appearance',
            'sidebar_state',
            'fingerprint_id',
            '__stripe_mid',
        ]);

        $middleware->api([
            AddQueuedCookiesToResponse::class,
        ]);

        $middleware->web(append: [
            CreateFreshApiToken::class,
            CheckBannedUser::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if ($request->expectsJson()) {
                return $response;
            }

            if ($exception instanceof BannedException) {
                return Inertia::render('banned', [
                    'user' => $exception->fingerprint->user,
                    'fingerprint' => $exception->fingerprint,
                    'banReason' => $exception->fingerprint?->ban_reason,
                    'bannedAt' => $exception->fingerprint?->banned_at,
                    'bannedBy' => $exception->fingerprint?->bannedBy?->name,
                ]);
            }

            if (in_array($response->getStatusCode(), [500, 503, 404, 403]) && ! config('app.debug')) {
                return Inertia::render('error', [
                    'status' => (string) $response->getStatusCode(),
                    'message' => in_array($exception->getMessage(), ['', '0'], true) ? 'An error occurred' : $exception->getMessage(),
                ]);
            }

            return $response;
        });
    })->create();
