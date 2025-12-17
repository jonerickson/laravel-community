<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\SubscriptionData;
use App\Enums\BillingReason;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Discount;
use App\Models\Order;
use App\Models\User;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Random\RandomException;
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

    /**
     * @throws Throwable
     */
    public function applyDiscountsToOrder(Order $order, array $discounts): float
    {
        return DB::transaction(function () use ($order, $discounts): float {
            $order->load(['items', 'discounts']);
            $orderTotal = (int) ($order->amount_subtotal * 100);
            $remainingTotal = $orderTotal;
            $totalDiscount = 0;

            /** @var Discount $discount */
            foreach ($discounts as $discount) {
                $discountAmount = $this->calculateDiscount($order, $discount);

                if ($discountAmount > 0) {
                    $order->discounts()->attach($discount->id, [
                        'amount_applied' => $discountAmount,
                        'balance_before' => $discount->type === DiscountType::GiftCard
                            ? $discount->current_balance
                            : null,
                        'balance_after' => $discount->type === DiscountType::GiftCard
                            ? max(0, $discount->current_balance - $discountAmount)
                            : null,
                    ]);

                    $totalDiscount += $discountAmount;
                    $remainingTotal -= $discountAmount;
                }

                if ($remainingTotal <= 0) {
                    break;
                }
            }

            return (float) $totalDiscount;
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
            'code' => $code ? Str::upper($code) : $this->generateUniqueCode(),
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

    /**
     * @throws RandomException
     */
    public function cancellationOfferIsAvailable(User $user, SubscriptionData $subscription): bool
    {
        $previousOfferHasBeenUsed = $user
            ->orders()
            ->whereRelation('discounts', 'type', DiscountType::Cancellation)
            ->exists();

        if ($previousOfferHasBeenUsed) {
            return false;
        }

        $userRenewals = $user
            ->orders()
            ->where('billing_reason', BillingReason::SubscriptionCycle)
            ->whereRelation('items', 'price_id', $subscription->price?->id)
            ->whereDoesntHaveRelation('discounts', 'type', DiscountType::Cancellation)
            ->count();

        if ($userRenewals <= 0) {
            return false;
        }

        if ($userRenewals > 3) {
            return true;
        }

        return (bool) random_int(0, 1);
    }

    public function generateUniqueCode(DiscountType $type = DiscountType::PromoCode, int $attempts = 5): string
    {
        for ($i = 0; $i < $attempts; $i++) {
            $code = new Discount()->generateCode($type);

            if (! Discount::query()->where('code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('Failed to generate unique discount code after '.$attempts.' attempts.');
    }

    public function calculateDiscount(Order $order, Discount $discount): float
    {
        if (! $discount->is_valid) {
            return 0;
        }

        if (filled($discount->min_order_amount) && $order->amount_subtotal < $discount->min_order_amount) {
            return 0;
        }

        return match ($discount->discount_type) {
            DiscountValueType::Fixed => $this->calculateFixedDiscount($discount, $order->amount_subtotal),
            DiscountValueType::Percentage => $this->calculatePercentageDiscount($discount, $order->amount_subtotal),
        };
    }

    protected function calculateFixedDiscount(Discount $discount, float $orderTotal): float
    {
        if ($discount->type === DiscountType::GiftCard) {
            return min($discount->current_balance, $orderTotal);
        }

        return min($discount->value, $orderTotal);
    }

    protected function calculatePercentageDiscount(Discount $discount, float $orderTotal): float
    {
        $discount = round($orderTotal * ($discount->value / 100));

        return min($discount, $orderTotal);
    }
}
