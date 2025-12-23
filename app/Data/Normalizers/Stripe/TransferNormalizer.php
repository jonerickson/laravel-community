<?php

declare(strict_types=1);

namespace App\Data\Normalizers\Stripe;

use Spatie\LaravelData\Normalizers\Normalized\Normalized;
use Spatie\LaravelData\Normalizers\Normalizer;
use Stripe\Transfer;

class TransferNormalizer implements Normalizer
{
    public function normalize(mixed $value): null|array|Normalized
    {
        if ($value instanceof Transfer) {
            return [
                'id' => $value->id,
                'amount' => $value->amount / 100,
                'currency' => $value->currency,
                'destination' => $value->destination,
                'source_transaction' => $value->source_transaction,
                'metadata' => $value->metadata?->toArray(),
                'reversed' => $value->reversed,
                'created_at' => $value->created ? now()->setTimestamp($value->created) : null,
            ];
        }

        return null;
    }
}
