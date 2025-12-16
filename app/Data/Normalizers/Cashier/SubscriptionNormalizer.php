<?php

declare(strict_types=1);

namespace App\Data\Normalizers\Cashier;

use App\Data\PriceData;
use App\Data\ProductData;
use App\Data\UserData;
use App\Enums\SubscriptionStatus;
use App\Models\Price;
use Laravel\Cashier\Subscription;
use Spatie\LaravelData\Normalizers\Normalized\Normalized;
use Spatie\LaravelData\Normalizers\Normalizer;

class SubscriptionNormalizer implements Normalizer
{
    public function normalize(mixed $value): null|array|Normalized
    {
        if ($value instanceof Subscription) {
            return [
                'name' => $value->type,
                'user' => UserData::from($value->user),
                'status' => SubscriptionStatus::tryFrom($value->stripe_status ?? ''),
                'trialEndsAt' => $value->trial_ends_at?->toImmutable(),
                'endsAt' => $value->ends_at?->toImmutable(),
                'createdAt' => $value->created_at?->toImmutable(),
                'updatedAt' => $value->updated_at?->toImmutable(),
                'price' => PriceData::from($price = Price::query()->where('external_price_id', $value->stripe_price)->first()),
                'product' => ProductData::from($product = $price?->product),
                'externalSubscriptionId' => $value->stripe_id,
                'externalProductId' => $product?->external_product_id,
                'externalPriceId' => $value->stripe_price,
                'quantity' => $value->quantity,
            ];
        }

        return null;
    }
}
