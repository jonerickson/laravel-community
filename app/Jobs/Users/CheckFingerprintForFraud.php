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
        if ($fingerprintService->isSuspicious($this->fingerprint->request_id)) {
            $this->fingerprint->blacklistResource(
                reason: 'Automatically blacklisted due to suspicious account activity.'
            );
        }

        $this->fingerprint->update([
            'last_checked_at' => now(),
        ]);
    }
}
