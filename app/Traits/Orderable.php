<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Orderable
{
    public static function getNextOrder(): int
    {
        return (static::max('order') ?? 0) + 1;
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('order');
    }

    public function moveUp(): void
    {
        $previous = static::where('order', '<', $this->order)
            ->orderByDesc('order')
            ->first();

        if ($previous) {
            $this->swapOrder($previous);
        }
    }

    public function moveDown(): void
    {
        $next = static::where('order', '>', $this->order)
            ->orderBy('order')
            ->first();

        if ($next) {
            $this->swapOrder($next);
        }
    }

    protected static function bootOrderable(): void
    {
        static::creating(function ($model): void {
            if (! isset($model->order)) {
                $model->fill([
                    'order' => static::getNextOrder(),
                ]);
            }
        });
    }

    protected function initializeOrderable(): void
    {
        $this->mergeFillable([
            'order',
        ]);
    }

    protected function swapOrder($other): void
    {
        $tempOrder = $this->order;
        $this->update(['order' => $other->order]);
        $other->update(['order' => $tempOrder]);
    }
}
