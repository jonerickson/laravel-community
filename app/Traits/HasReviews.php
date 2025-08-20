<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasReviews
{
    public function reviews(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->ratings();
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->comments();
    }

    protected function initializeHasReviews(): void
    {
        $this->mergeAppends([
            'average_rating',
            'reviews_count',
        ]);
    }

    protected function averageRating(): Attribute
    {
        return Attribute::make(
            get: fn () => (float) $this->reviews()->avg('rating') ?: 0
        )->shouldCache();
    }

    protected function reviewsCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->reviews()->count()
        )->shouldCache();
    }
}
