<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property int $order_id
 * @property int $discount_id
 * @property float $amount_applied
 * @property float|null $balance_before
 * @property float|null $balance_after
 * @property string|null $external_discount_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderDiscount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderDiscount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderDiscount query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderDiscount whereAmountApplied($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderDiscount whereBalanceAfter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderDiscount whereBalanceBefore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderDiscount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderDiscount whereDiscountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderDiscount whereExternalDiscountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderDiscount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderDiscount whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderDiscount whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class OrderDiscount extends Pivot
{
    protected $table = 'orders_discounts';

    protected $attributes = [
        'amount_applied' => 0,
    ];

    protected $fillable = [
        'order_id',
        'discount_id',
        'amount_applied',
        'balance_before',
        'balance_after',
        'external_discount_id',
    ];

    public function amountApplied(): Attribute
    {
        return Attribute::make(
            get: fn (int $value): float => (float) $value / 100,
            set: fn (float $value): int => (int) ($value * 100),
        );
    }

    public function balanceBefore(): Attribute
    {
        return Attribute::make(
            get: fn (?int $value): ?float => filled($value) ? (float) $value / 100 : null,
            set: fn (?float $value): ?int => filled($value) ? (int) ($value * 100) : null,
        );
    }

    public function balanceAfter(): Attribute
    {
        return Attribute::make(
            get: fn (?int $value): ?float => filled($value) ? (float) $value / 100 : null,
            set: fn (?float $value): ?int => filled($value) ? (int) ($value * 100) : null,
        );
    }
}
