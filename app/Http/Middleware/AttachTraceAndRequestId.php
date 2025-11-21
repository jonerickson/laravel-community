<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

class AttachTraceAndRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Request-Id', Context::get('request_id'));
        $response->headers->set('X-Trace-Id', Context::get('trace_id'));

        return $response;
    }
}
