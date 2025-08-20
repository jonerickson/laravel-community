<?php

declare(strict_types=1);

namespace App\Traits;

trait HasOrder
{
    public static function getNextOrder(): int
    {
        return (static::max('order') ?? 0) + 1;
    }

    public static function bootHasOrder(): void
    {
        static::creating(function ($model) {
            if (is_null($model->order)) {
                $model->order = static::getNextOrder();
            }
        });
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
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

    protected function initializeHasOrder(): void
    {
        $this->fillable = array_merge($this->fillable ?? [], ['order']);
    }

    protected function swapOrder($other): void
    {
        $tempOrder = $this->order;
        $this->update(['order' => $other->order]);
        $other->update(['order' => $tempOrder]);
    }
}
