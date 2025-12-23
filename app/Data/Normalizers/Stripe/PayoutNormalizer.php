<?php

declare(strict_types=1);

namespace App\Data\Normalizers\Stripe;

use Spatie\LaravelData\Normalizers\Normalized\Normalized;
use Spatie\LaravelData\Normalizers\Normalizer;
use Stripe\Payout;

class PayoutNormalizer implements Normalizer
{
    public function normalize(mixed $value): null|array|Normalized
    {
        if ($value instanceof Payout) {
            return [
                'id' => $value->id,
                'amount' => $value->amount / 100,
                'currency' => $value->currency,
                'status' => $value->status,
                'arrival_date' => $value->arrival_date,
                'created' => $value->created,
                'method' => $value->method,
                'description' => $value->description,
            ];
        }

        return null;
    }
}
