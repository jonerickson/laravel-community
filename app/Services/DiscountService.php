<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Discount;
use App\Models\Order;
use App\Models\User;
use DateTime;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DiscountService
{
    public function validateDiscount(string $code): ?Discount
    {
        $discount = Discount::query()
            ->byCode($code)
            ->active()
            ->withBalance()
            ->first();

        if (blank($discount)) {
            return null;
        }

        if (! $discount->is_valid) {
            return null;
        }

        return $discount;
    }

    public function calculateOrderDiscounts(Order $order, array $discountCodes): Collection
    {
        $orderTotal = $order->amount;
        $discounts = collect();
        $remainingTotal = $orderTotal;

        foreach ($discountCodes as $code) {
            $discount = $this->validateDiscount($code);

            if (blank($discount)) {
                continue;
            }

            $discountAmount = $discount->calculateDiscount($remainingTotal);

            if ($discountAmount > 0) {
                $discounts->push([
                    'discount' => $discount,
                    'amount' => $discountAmount,
                ]);

                $remainingTotal -= $discountAmount;
            }

            if ($remainingTotal <= 0) {
                break;
            }
        }

        return $discounts;
    }

    /**
     * @throws Throwable
     */
    public function applyDiscountsToOrder(Order $order, array $discounts): int
    {
        return DB::transaction(function () use ($order, $discounts): int|float {
            $order->load(['items', 'discounts']);
            $orderTotal = (int) ($order->amount * 100);
            $remainingTotal = $orderTotal;
            $totalDiscount = 0;

            /** @var Discount $discount */
            foreach ($discounts as $discount) {
                $discountAmount = $discount->calculateDiscount($remainingTotal);

                if ($discountAmount > 0) {
                    $order->discounts()->attach($discount->id, [
                        'amount_applied' => $discountAmount / 100,
                        'balance_before' => $discount->type === DiscountType::GiftCard ? $discount->getRawOriginal('current_balance') / 100 : null,
                        'balance_after' => $discount->type === DiscountType::GiftCard ? (max(0, $discount->getRawOriginal('current_balance') - $discountAmount)) / 100 : null,
                    ]);

                    $totalDiscount += $discountAmount;
                    $remainingTotal -= $discountAmount;
                }

                if ($remainingTotal <= 0) {
                    break;
                }
            }

            return $totalDiscount;
        });
    }

    public function createGiftCard(int $value, ?int $productId = null, ?int $userId = null, ?string $recipientEmail = null): Discount
    {
        return Discount::create([
            'code' => $this->generateUniqueCode(DiscountType::GiftCard),
            'type' => DiscountType::GiftCard,
            'discount_type' => DiscountValueType::Fixed,
            'value' => $value,
            'current_balance' => $value,
            'product_id' => $productId,
            'user_id' => $userId,
            'recipient_email' => $recipientEmail,
        ]);
    }

    public function createPromoCode(?string $code = null, int $value = 100, DiscountValueType $discountType = DiscountValueType::Percentage, ?int $maxUses = null, ?int $minOrderAmount = null, ?DateTime $expiresAt = null, ?User $user = null): Discount
    {
        return Discount::create([
            'code' => $code ? Str::upper($code) : $this->generateUniqueCode(DiscountType::PromoCode),
            'type' => DiscountType::PromoCode,
            'discount_type' => $discountType,
            'value' => $value,
            'max_uses' => $maxUses,
            'min_order_amount' => $minOrderAmount,
            'expires_at' => $expiresAt,
            'user_id' => $user?->id,
        ]);
    }

    public function createCancellationOffer(User $user, ?DateTime $expiresAt = null): Discount
    {
        return Discount::create([
            'code' => $this->generateUniqueCode(DiscountType::Cancellation),
            'type' => DiscountType::Cancellation,
            'discount_type' => DiscountValueType::Percentage,
            'value' => 20,
            'max_uses' => 1,
            'expires_at' => $expiresAt,
            'user_id' => $user->id,
        ]);
    }

    public function generateUniqueCode(DiscountType $type = DiscountType::PromoCode, int $attempts = 5, ?string $prefix = null): string
    {
        for ($i = 0; $i < $attempts; $i++) {
            $prefix ??= match ($type) {
                DiscountType::Cancellation => 'CANCELLATION-OFFER',
                DiscountType::GiftCard => 'GIFT',
                DiscountType::PromoCode => 'PROMO',
                DiscountType::Manual => 'MANUAL',
            };

            $code = Str::upper($prefix.'-'.Str::random(4).'-'.Str::random(4).'-'.Str::random(4));

            if (! Discount::query()->where('code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('Failed to generate unique discount code after '.$attempts.' attempts.');
    }

    public function getDiscountsByUser(int $userId): Collection
    {
        return Discount::query()
            ->where('user_id', $userId)
            ->active()
            ->withBalance()
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
