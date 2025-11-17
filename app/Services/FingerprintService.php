<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Container\Attributes\Config;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class FingerprintService
{
    protected string $baseUrl = 'https://api.fpjs.io/v4';

    public function __construct(
        #[Config('services.fingerprint.api_key')]
        protected ?string $apiKey,
        #[Config('services.fingerprint.suspect_score_threshold')]
        protected float $suspectScoreThreshold = 0.75,
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

        return $response->json();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function isSuspicious(string $requestId): bool
    {
        $eventData = $this->getEventData($requestId);

        if (blank($eventData)) {
            return false;
        }

        $suspectScore = $eventData['suspect_score'] ?? 0;

        return $suspectScore >= $this->suspectScoreThreshold;
    }
}
