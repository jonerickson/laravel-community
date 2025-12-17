<?php

declare(strict_types=1);

namespace App\Data\Normalizers\Cashier;

use App\Models\User;
use Laravel\Cashier\PaymentMethod;
use Spatie\LaravelData\Normalizers\Normalized\Normalized;
use Spatie\LaravelData\Normalizers\Normalizer;

class PaymentMethodNormalizer implements Normalizer
{
    public function normalize(mixed $value): null|array|Normalized
    {
        if ($value instanceof PaymentMethod) {
            $paymentMethod = $value->asStripePaymentMethod();
            $customer = $paymentMethod->customer;
            $user = User::query()->where('stripe_id', $customer)->first();

            return [
                'id' => $paymentMethod->id,
                'type' => $paymentMethod->type,
                'brand' => $paymentMethod->card->brand ?? null,
                'last4' => $paymentMethod->card->last4 ?? null,
                'expMonth' => $paymentMethod->card->exp_month ?? null,
                'expYear' => $paymentMethod->card->exp_year ?? null,
                'holderName' => $paymentMethod->billing_details->name ?? null,
                'holderEmail' => $paymentMethod->billing_details->email ?? null,
                'isDefault' => $user?->defaultPaymentMethod()?->id === $paymentMethod->id,
            ];
        }

        return null;
    }
}
