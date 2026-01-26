<?php

declare(strict_types=1);

use App\Services\FingerprintService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Psr\Log\NullLogger;

function createFingerprintService(?string $apiKey = 'test-api-key', float $suspectScoreThreshold = 0.7): FingerprintService
{
    return new FingerprintService(
        apiKey: $apiKey,
        suspectScoreThreshold: $suspectScoreThreshold,
        log: new NullLogger
    );
}

describe('getEventData', function (): void {
    test('returns null when api key is blank', function (): void {
        $service = createFingerprintService(apiKey: null);

        $result = $service->getEventData('request-123');

        expect($result)->toBeNull();
    });

    test('returns null when api key is empty string', function (): void {
        $service = createFingerprintService(apiKey: '');

        $result = $service->getEventData('request-123');

        expect($result)->toBeNull();
    });

    test('returns event data on successful request', function (): void {
        Http::fake([
            'api.fpjs.io/*' => Http::response([
                'requestId' => 'request-123',
                'visitorId' => 'visitor-456',
                'ipInfo' => [
                    'v4' => ['address' => '192.168.1.1'],
                ],
            ], 200),
        ]);

        $service = createFingerprintService();
        $result = $service->getEventData('request-123');

        expect($result)
            ->toBeArray()
            ->toHaveKey('requestId', 'request-123')
            ->toHaveKey('visitorId', 'visitor-456');

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.fpjs.io/v4/events/request-123'
            && $request->hasHeader('Authorization', 'Bearer test-api-key')
            && $request->hasHeader('Accept', 'application/json'));
    });

    test('throws RequestException on 4xx error', function (): void {
        Http::fake([
            'api.fpjs.io/*' => Http::response([
                'error' => 'Not found',
            ], 404),
        ]);

        $service = createFingerprintService();

        expect(fn (): ?array => $service->getEventData('invalid-request'))
            ->toThrow(RequestException::class);
    });

    test('throws RequestException on 5xx error', function (): void {
        Http::fake([
            'api.fpjs.io/*' => Http::response([
                'error' => 'Internal server error',
            ], 500),
        ]);

        $service = createFingerprintService();

        expect(fn (): ?array => $service->getEventData('request-123'))
            ->toThrow(RequestException::class);
    });

    test('throws ConnectionException on connection failure', function (): void {
        Http::fake([
            'api.fpjs.io/*' => fn () => throw new ConnectionException('Connection refused'),
        ]);

        $service = createFingerprintService();

        expect(fn (): ?array => $service->getEventData('request-123'))
            ->toThrow(ConnectionException::class);
    });

    test('includes request id in URL', function (): void {
        Http::fake([
            'api.fpjs.io/*' => Http::response(['data' => 'test'], 200),
        ]);

        $service = createFingerprintService();
        $service->getEventData('my-unique-request-id');

        Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'my-unique-request-id'));
    });

    test('uses bearer token authentication', function (): void {
        Http::fake([
            'api.fpjs.io/*' => Http::response(['data' => 'test'], 200),
        ]);

        $service = createFingerprintService(apiKey: 'my-secret-api-key');
        $service->getEventData('request-123');

        Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer my-secret-api-key'));
    });
});
