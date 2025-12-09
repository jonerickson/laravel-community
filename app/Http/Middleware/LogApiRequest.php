<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Log;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Uri;
use JsonException;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    /**
     * @throws JsonException
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isFirstParty($request)) {
            return $next($request);
        }

        $user = Auth::guard('api')->user();

        $log = Log::create([
            'endpoint' => $request->getPathInfo(),
            'method' => strtolower($request->getMethod()),
            'request_headers' => collect($request->headers)->reject(fn ($header, $value): bool => in_array(strtolower($value), [
                'cookie',
                'authorization',
                'x-csrf-token',
                'x-xsrf-token',
                'php-auth-pw',
            ]))->map(fn ($header): string => implode(', ', $header))->all(),
            'request_body' => $this->captureRequestBody($request),
            'loggable_type' => $user ? $user::class : null,
            'loggable_id' => $user ? $user->getKey() : null,
        ]);

        $request->attributes->add([
            'api_log_id' => $log->getKey(),
        ]);

        return $next($request);
    }

    private function isFirstParty(Request $request): bool
    {
        return Uri::of($request->headers->get('origin') ?? '')->host() === Uri::of(config('app.url'))->host();
    }

    /**
     * @throws JsonException
     */
    private function captureRequestBody(Request $request): mixed
    {
        $contentType = $request->header('Content-Type', '');

        if (Str::startsWith($contentType, 'multipart/form-data')) {
            return $this->processMultipartData($request->all());
        }

        $content = $request->getContent();

        if (Str::isJson($content)) {
            return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        }

        if (! mb_check_encoding($content, 'UTF-8')) {
            return null;
        }

        return $content;
    }

    private function processMultipartData(mixed $data): mixed
    {
        if ($data instanceof UploadedFile) {
            return [
                'type' => 'file',
                'name' => $data->getClientOriginalName(),
                'size' => $data->getSize(),
                'mime_type' => $data->getMimeType(),
            ];
        }

        if (is_array($data)) {
            return array_map($this->processMultipartData(...), $data);
        }

        return $data;
    }
}
