<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Container\Attributes\Config;
use Illuminate\Container\Attributes\Log;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;

class CloudflareService
{
    protected string $baseUrl = 'https://api.cloudflare.com/client/v4/';

    public function __construct(
        #[Config('services.cloudflare.api_key')]
        protected ?string $apiToken,
        #[Config('services.cloudflare.zone_id')]
        protected ?string $zoneId,
        #[Log]
        protected LoggerInterface $log,
    ) {
        //
    }

    /**
     * @throws ConnectionException
     */
    public function purgeCache(): bool
    {
        $response = $this->client()->post(sprintf('/zones/%s/purge_cache', $this->zoneId), [
            'purge_everything' => true,
        ]);

        if (! $response->successful()) {
            $this->log->error('Cloudflare API request failed', [
                'body' => $response->json(),
            ]);

            return false;
        }

        return true;
    }

    protected function client(): PendingRequest|Factory
    {
        return Http::withToken($this->apiToken)
            ->withUserAgent(config('app.name'))
            ->acceptJson()
            ->baseUrl($this->baseUrl);
    }
}
