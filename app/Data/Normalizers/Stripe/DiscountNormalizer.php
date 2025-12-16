<?php

declare(strict_types=1);

namespace App\Data\Normalizers\Stripe;

use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use Spatie\LaravelData\Normalizers\Normalized\Normalized;
use Spatie\LaravelData\Normalizers\Normalizer;
use Stripe\Discount;

class DiscountNormalizer implements Normalizer
{
    public function normalize(mixed $value): null|array|Normalized
    {
        if ($value instanceof Discount) {
            return [
                'type' => DiscountType::Manual,
                'discountType' => $value->coupon->percent_off ? DiscountValueType::Percentage : DiscountValueType::Fixed,
                'value' => ($value->coupon->percent_off ?? (float) $value->coupon->amount_off ?? 0) / 100,
                'code' => $value->coupon->name,
                'externalDiscountId' => $value->id,
                'externalCouponId' => $value->coupon->id,
                'maxUses' => $value->coupon->max_redemptions,
                'timesUsed' => $value->coupon->times_redeemed,
                'isValid' => $value->coupon->valid,
            ];
        }

        return null;
    }
}
