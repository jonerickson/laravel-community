<?php

declare(strict_types=1);

namespace App\Data\Normalizers\Stripe;

use Spatie\LaravelData\Normalizers\Normalized\Normalized;
use Spatie\LaravelData\Normalizers\Normalizer;
use Stripe\Customer;

class CustomerNormalizer implements Normalizer
{
    public function normalize(mixed $value): null|array|Normalized
    {
        if ($value instanceof Customer) {
            return [
                'id' => $value->id,
                'email' => $value->email,
                'name' => $value->name,
                'phone' => $value->phone,
                'currency' => $value->currency,
                'metadata' => $value->metadata->toArray(),
            ];
        }

        return null;
    }
}
