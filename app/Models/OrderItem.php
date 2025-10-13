<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Number;
use Override;

/**
 * @property int $id
 * @property int $order_id
 * @property string|null $name
 * @property int|null $product_id
 * @property int|null $price_id
 * @property int|float $amount
 * @property string $commission_amount
 * @property int $quantity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Order $order
 * @property-read Price|null $price
 * @property-read Product|null $product
 *
 * @method static \Database\Factories\OrderItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereCommissionAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem wherePriceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class OrderItem extends Model implements HasLabel
{
    use HasFactory;

    protected $table = 'orders_items';

    protected $attributes = [
        'quantity' => 1,
    ];

    protected $fillable = [
        'order_id',
        'product_id',
        'price_id',
        'quantity',
        'name',
        'amount',
        'commission_amount',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function price(): BelongsTo
    {
        return $this->belongsTo(Price::class);
    }

    public function getLabel(): string|Htmlable|null
    {
        $product = $this->name ?? $this->product?->name ?? 'Unknown Product';
        $price = ($this->getOriginal('amount')
            ? Number::currency($this->amount)
            : $this->price?->getLabel()) ?? 'Unknown Price';

        return "$product - $price";
    }

    public function amount(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes): int|float {
                if ($amount = data_get($attributes, 'amount')) {
                    return ($amount * $this->quantity) / 100;
                }

                return ($this->price->amount ?? 0) * $this->quantity;
            },
            set: fn (float $value): int => (int) ($value * 100),
        );
    }

    public function commissionAmount(): Attribute
    {
        return Attribute::make(
            get: fn (int $value): float => $value / 100,
            set: fn (float $value): int => (int) ($value * 100),
        );
    }

    #[Override]
    protected static function booted(): void
    {
        static::creating(function (OrderItem $model): void {
            if (blank($model->name)) {
                $model->fill([
                    'name' => $model->getLabel(),
                ]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }
}
