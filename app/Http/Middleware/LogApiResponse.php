<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Log;
use App\Services\JsonService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogApiResponse
{
    public function __construct(
        private readonly JsonService $jsonService,
    ) {
        //
    }

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if ($response instanceof StreamedResponse) {
            return $response;
        }

        $logId = $request->attributes->get('api_log_id');

        if (blank($logId)) {
            return $response;
        }

        $log = Log::find($logId);

        if (blank($log)) {
            return $response;
        }

        $processed = $this->jsonService->processForLogging($response->getContent());

        $log->update([
            'status' => $response->getStatusCode(),
            'response_headers' => collect($response->headers)->map(fn ($header): string => implode(', ', $header))->all(),
            'response_content' => $processed['content'] ?? null,
        ]);

        return $response;
    }
}
