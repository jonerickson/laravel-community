<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Container\Attributes\Config;
use Illuminate\Container\Attributes\Log;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;

class FingerprintService
{
    protected string $baseUrl = 'https://api.fpjs.io/v4';

    public function __construct(
        #[Config('services.fingerprint.api_key')]
        protected ?string $apiKey,
        #[Config('services.fingerprint.suspect_score_threshold')]
        protected float $suspectScoreThreshold,
        #[Log]
        protected LoggerInterface $log,
    ) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getEventData(string $requestId): ?array
    {
        if (blank($this->apiKey)) {
            return null;
        }

        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->get(sprintf('%s/events/%s', $this->baseUrl, $requestId))
            ->throw();

        if (! $response->successful()) {
            $this->log->error('Fingerprint API request failed', [
                'body' => $response->json(),
            ]);

            return null;
        }

        return $response->json();
    }
}
