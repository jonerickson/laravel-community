<?php

declare(strict_types=1);

namespace App\Data\Normalizers\Stripe;

use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use Spatie\LaravelData\Normalizers\Normalized\Normalized;
use Spatie\LaravelData\Normalizers\Normalizer;
use Stripe\Coupon;

class CouponNormalizer implements Normalizer
{
    public function normalize(mixed $value): null|array|Normalized
    {
        if ($value instanceof Coupon) {
            return [
                'id' => 0,
                'type' => DiscountType::Manual,
                'discountType' => $value->percent_off ? DiscountValueType::Percentage : DiscountValueType::Fixed,
                'value' => ($value->percent_off ?? (float) $value->amount_off ?? 0) / 100,
                'code' => $value->name,
                'externalCouponId' => $value->id,
                'maxUses' => $value->max_redemptions,
            ];
        }

        return null;
    }
}
