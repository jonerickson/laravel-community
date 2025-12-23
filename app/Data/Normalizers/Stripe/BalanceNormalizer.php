<?php

declare(strict_types=1);

namespace App\Data\Normalizers\Stripe;

use Spatie\LaravelData\Normalizers\Normalized\Normalized;
use Spatie\LaravelData\Normalizers\Normalizer;
use Stripe\Balance;

class BalanceNormalizer implements Normalizer
{
    public function normalize(mixed $value): null|array|Normalized
    {
        if ($value instanceof Balance) {
            $available = 0.0;
            $pending = 0.0;

            foreach ($value->available as $item) {
                $available += $item->amount / 100;
            }

            foreach ($value->pending as $item) {
                $pending += $item->amount / 100;
            }

            return [
                'available' => $available,
                'pending' => $pending,
                'currency' => $value->available[0]->currency ?? 'usd',
                'breakdown' => [
                    'available' => $value->available,
                    'pending' => $value->pending,
                ],
            ];
        }

        return null;
    }
}
