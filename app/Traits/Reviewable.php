<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Reviewable
{
    public function reviews(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->ratings();
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->comments();
    }

    public function averageRating(): Attribute
    {
        return Attribute::make(
            get: fn (): float|int => (float) $this->reviews()->avg('rating') ?: 0
        )->shouldCache();
    }

    public function reviewsCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->reviews()->count()
        )->shouldCache();
    }

    protected function initializeReviewable(): void
    {
        $this->mergeAppends([
            'average_rating',
            'reviews_count',
        ]);
    }
}
