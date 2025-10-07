<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\PublishableStatus;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * @mixin Eloquent
 */
trait Publishable
{
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->wherePast('published_at');
    }

    public function scopeUnpublished(Builder $query): void
    {
        $query->where('is_published', false)
            ->orWhere(function (Builder $query): void {
                $query->whereNotNull('published_at')
                    ->whereFuture('published_at');
            });
    }

    public function scopeRecent(Builder $query): void
    {
        $query->orderBy('published_at', 'desc');
    }

    public function isPublished(): bool
    {
        return $this->is_published
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    public function status(): Attribute
    {
        return Attribute::get(function (): PublishableStatus {
            if ($this->isPublished()) {
                return PublishableStatus::Published;
            }

            return PublishableStatus::Draft;
        })->shouldCache();
    }

    protected function initializePublishable(): void
    {
        $this->mergeCasts([
            'status' => PublishableStatus::class,
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ]);

        $this->mergeFillable([
            'is_published',
            'published_at',
        ]);
    }
}
