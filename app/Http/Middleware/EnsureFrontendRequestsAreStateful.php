<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Routing\Pipeline;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureFrontendRequestsAreStateful
{
    public function handle(Request $request, Closure $next): Response
    {
        $this->configureSecureCookieSessions();

        return (new Pipeline(app()))->send($request)->through(
            static::fromFrontend($request) ? $this->frontendMiddleware() : []
        )->then(function ($request) use ($next) {
            return $next($request);
        });
    }

    protected static function fromFrontend(Request $request): bool
    {
        $domain = $request->headers->get('referer') ?: $request->headers->get('origin');

        if (is_null($domain)) {
            return false;
        }

        $domain = Str::replaceFirst('https://', '', $domain);
        $domain = Str::replaceFirst('http://', '', $domain);
        $domain = Str::endsWith($domain, '/') ? $domain : "$domain/";

        $appUrl = config('app.url');

        $stateful = array_filter([
            '%s%s',
            'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
            $appUrl ? parse_url($appUrl, PHP_URL_HOST).(parse_url($appUrl, PHP_URL_PORT) ? ':'.parse_url($appUrl, PHP_URL_PORT) : '') : '',
        ]);

        return Str::is(Collection::make($stateful)->map(function ($uri) {
            return trim($uri).'/*';
        })->all(), $domain);
    }

    protected function configureSecureCookieSessions(): void
    {
        config([
            'session.http_only' => true,
            'session.same_site' => 'lax',
        ]);
    }

    protected function frontendMiddleware(): array
    {
        $middleware = [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            VerifyCsrfToken::class,
        ];

        array_unshift($middleware, function (Request $request, Closure $next) {
            return $next($request);
        });

        return $middleware;
    }
}
