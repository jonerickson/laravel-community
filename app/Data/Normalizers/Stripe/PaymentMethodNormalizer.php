<?php

declare(strict_types=1);

namespace App\Data\Normalizers\Stripe;

use App\Models\User;
use Spatie\LaravelData\Normalizers\Normalized\Normalized;
use Spatie\LaravelData\Normalizers\Normalizer;
use Stripe\PaymentMethod;

class PaymentMethodNormalizer implements Normalizer
{
    public function normalize(mixed $value): null|array|Normalized
    {
        if ($value instanceof PaymentMethod) {
            $user = User::query()->where('stripe_id', $value->customer)->first();

            return [
                'id' => $value->id,
                'type' => $value->type,
                'brand' => $value->card->brand ?? null,
                'last4' => $value->card->last4 ?? null,
                'expMonth' => $value->card->exp_month ?? null,
                'expYear' => $value->card->exp_year ?? null,
                'holderName' => $value->billing_details->name ?? null,
                'holderEmail' => $value->billing_details->email ?? null,
                'isDefault' => $user?->defaultPaymentMethod()?->id === $value->id,
            ];
        }

        return null;
    }
}
