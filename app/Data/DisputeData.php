<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;

class DisputeData extends Data
{
    public function __construct(
        public string $externalDisputeId,
        public string $externalChargeId,
        public ?string $externalPaymentIntentId,
        public string $status,
        public ?string $reason,
        public int $amount,
        public string $currency,
        public ?Carbon $evidenceDueBy,
        public bool $isChargeRefundable,
        public ?string $networkReasonCode,
    ) {}
}
