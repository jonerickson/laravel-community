<?php

declare(strict_types=1);

namespace App\Data\Normalizers\Stripe;

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
                'exp_month' => $paymentMethod->card->exp_month ?? null,
                'exp_year' => $paymentMethod->card->exp_year ?? null,
                'holder_name' => $paymentMethod->billing_details->name ?? null,
                'email' => $value->billing_details->email ?? null,
                'is_default' => $user?->defaultPaymentMethod()?->id === $paymentMethod->id,
            ];
        }

        return null;
    }
}
