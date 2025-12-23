<?php

declare(strict_types=1);

namespace App\Data\Normalizers\Stripe;

use Spatie\LaravelData\Normalizers\Normalized\Normalized;
use Spatie\LaravelData\Normalizers\Normalizer;
use Stripe\Account;

class AccountNormalizer implements Normalizer
{
    public function normalize(mixed $value): null|array|Normalized
    {
        if ($value instanceof Account) {
            return [
                'id' => $value->id,
                'email' => $value->email,
                'business_name' => $value->business_profile?->name,
                'charges_enabled' => $value->charges_enabled,
                'payouts_enabled' => $value->payouts_enabled,
                'details_submitted' => $value->details_submitted,
                'capabilities' => $value->capabilities?->toArray(),
                'requirements' => $value->requirements?->toArray(),
                'country' => $value->country,
                'default_currency' => $value->default_currency,
            ];
        }

        return null;
    }
}
