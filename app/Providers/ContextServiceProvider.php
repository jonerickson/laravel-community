<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class ContextServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $requestId = Str::uuid()->toString();
        $traceId = Str::uuid()->toString();

        Context::add('request_id', $requestId);
        Context::add('trace_id', $traceId);

        Http::globalRequestMiddleware(fn ($request) => $request->withHeader(
            'X-Trace-Id', $traceId
        ));
    }
}
