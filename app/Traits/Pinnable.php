<?php

declare(strict_types=1);

namespace App\Traits;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin Eloquent
 */
trait Pinnable
{
    public function scopePinned(Builder $query): void
    {
        $query->where('is_pinned', true);
    }

    public function scopeNotPinned(Builder $query): void
    {
        $query->where('is_pinned', false);
    }

    public function pin(): bool
    {
        return $this->update(['is_pinned' => true]);
    }

    public function unpin(): bool
    {
        return $this->update(['is_pinned' => false]);
    }

    public function togglePin(): bool
    {
        return $this->update(['is_pinned' => ! $this->is_pinned]);
    }

    protected function initializePinnable(): void
    {
        $this->mergeCasts([
            'is_pinned' => 'boolean',
        ]);

        $this->mergeFillable([
            'is_pinned',
        ]);
    }
}
