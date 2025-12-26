<?php

declare(strict_types=1);

use App\Data\UserData;
use App\Exceptions\BlacklistedException;
use App\Http\Middleware\AddSentryContext;
use App\Http\Middleware\AttachTraceAndRequestId;
use App\Http\Middleware\AuthorizeRequestAgainstBlacklist;
use App\Http\Middleware\EnsureAccountHasEmail;
use App\Http\Middleware\EnsureAccountHasPassword;
use App\Http\Middleware\ForceOnboarding;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LogApiRequest;
use App\Http\Middleware\LogApiResponse;
use App\Jobs\Api\ClearActivity;
use App\Jobs\Api\ClearLogs;
use App\Jobs\Store\ClearPendingOrders;
use App\Jobs\Store\ReleaseExpiredInventoryReservations;
use App\Jobs\Users\RemoveInactiveUsers;
use App\Models\Fingerprint;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Passport\Http\Middleware\CreateFreshApiToken;
use League\OAuth2\Server\Exception\OAuthServerException;
use Sentry\Laravel\Integration;
use Spatie\Csp\AddCspHeaders;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            require base_path('routes/misc.php');
        }
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('cache:prune-stale-tags')->hourly();
        $schedule->command('horizon:snapshot')->everyFiveMinutes();
        $schedule->command('mailbox:clean')->dailyAt('08:00');
        $schedule->command('queue:prune-failed --hours=96')->dailyAt('06:00');
        $schedule->command('queue:prune-batches --hours=96')->dailyAt('06:15');
        $schedule->command('telescope:prune --hours=96')->dailyAt('06:30');

        $schedule->job(ClearPendingOrders::class)->dailyAt('07:00');
        $schedule->job(ClearLogs::class)->hourly();
        $schedule->job(ClearActivity::class)->hourly();
        $schedule->job(RemoveInactiveUsers::class)->dailyAt('07:30');
        $schedule->job(ReleaseExpiredInventoryReservations::class)->hourly();
    })
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
            LogApiRequest::class,
            LogApiResponse::class,
        ]);

        $middleware->append([
            AttachTraceAndRequestId::class,
            AddSentryContext::class,
            AddCspHeaders::class,
        ]);

        $middleware->alias([
            'password' => EnsureAccountHasPassword::class,
            'email' => EnsureAccountHasEmail::class,
            'onboarded' => ForceOnboarding::class,
        ]);

        $middleware->web(append: [
            CreateFreshApiToken::class,
            AuthorizeRequestAgainstBlacklist::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->appendToPriorityList(EnsureEmailIsVerified::class, EnsureAccountHasEmail::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);

        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*'));

        $exceptions->dontReport([
            OAuthServerException::class,
            BlacklistedException::class,
        ]);

        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if ($request->expectsJson()) {
                return $response;
            }

            if ($exception instanceof BlacklistedException) {
                return Inertia::render('banned', [
                    'user' => UserData::from($exception->blacklist->resource instanceof Fingerprint
                        ? $exception->blacklist->resource->user
                        : $exception->blacklist->resource
                    ),
                    'fingerprint' => $exception->blacklist->resource,
                    'banReason' => $exception->blacklist->description,
                    'bannedAt' => $exception->blacklist->created_at,
                    'bannedBy' => UserData::from($exception->blacklist->author),
                ]);
            }

            if ($response->getStatusCode() === 419) {
                return back()->with([
                    'message' => 'The page expired, please try again.',
                    'messageVariant' => 'error',
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
