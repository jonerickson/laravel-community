<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use Exception;
use Illuminate\Container\Attributes\Config;
use Illuminate\Container\Attributes\Log;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Psr\Log\LoggerInterface;

class RobloxService
{
    protected string $baseUrl = 'https://groups.roblox.com/v1/';

    protected int $maxRetries = 3;

    public function __construct(
        #[Config('services.roblox.group_id')]
        protected ?string $groupId,
        #[Config('services.roblox.api_key')]
        protected ?string $apiKey,
        #[Log]
        protected LoggerInterface $log,
    ) {
        //
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getMemberCount(): int
    {
        $response = $this->makeRequest('get', sprintf('/groups/%s', $this->groupId));

        if (is_null($response)) {
            return 0;
        }

        return (int) $response->json('memberCount') ?? 0;
    }

    /**
     * Make a request to the Roblox API with rate limit and error handling.
     *
     * @param  array<string, string>|array<string, non-empty-array>  $options
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    protected function makeRequest(string $method, string $url, array $options = []): ?Response
    {
        try {
            return $this->client()
                ->retry($this->maxRetries, 0, function (Exception $exception): bool {
                    if ($exception instanceof ConnectionException) {
                        return true;
                    }

                    if ($exception instanceof RequestException) {
                        $response = $exception->response;
                        if ($response->status() === 429) {
                            $retryAfter = $response->header('Retry-After');

                            Sleep::until(Carbon::now()->addMilliseconds($retryAfter));

                            return true;
                        }

                        return $response->serverError();
                    }

                    return false;
                })
                ->{$method}($url, $options);
        } catch (ConnectionException $exception) {
            $this->log->error('Roblox API connection failed after retries', [
                'url' => $url,
                'method' => $method,
                'exception' => $exception->getMessage(),
            ]);
        } catch (RequestException $exception) {
            $this->log->error('Roblox API request failed', [
                'url' => $url,
                'method' => $method,
                'status' => $exception->response->status(),
                'body' => $exception->response->body(),
            ]);
        } catch (Exception $exception) {
            $this->log->error('Unexpected error during Roblox API request', [
                'url' => $url,
                'method' => $method,
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }

        return null;
    }

    protected function client(): PendingRequest|Factory
    {
        return Http::baseUrl($this->baseUrl)
            ->withUserAgent(config('app.name'))
            ->acceptJson();
    }
}
