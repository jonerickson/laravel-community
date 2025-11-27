<?php

declare(strict_types=1);

namespace App\Jobs\Users;

use App\Models\Fingerprint;
use App\Services\FingerprintService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class CheckFingerprintForFraud implements ShouldQueue
{
    use Queueable;

    public function __construct(public Fingerprint $fingerprint) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function handle(FingerprintService $fingerprintService): void
    {
        $suspectScoreThreshold = config('services.fingerprint.suspect_score_threshold');
        $suspectScore = 0;

        if (($eventData = $fingerprintService->getEventData($this->fingerprint->request_id)) && (($suspectScore = data_get($eventData, 'suspect_score') ?? 0) && $suspectScore >= $suspectScoreThreshold)) {
            $this->fingerprint->blacklistResource(
                reason: 'Automatically blacklisted due to suspicious account activity.'
            );
        }

        $this->fingerprint->update([
            'suspect_score' => $suspectScore,
            'last_checked_at' => now(),
        ]);
    }
}
