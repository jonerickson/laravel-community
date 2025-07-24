<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasComments
{
    public function initializeHasComments(): void
    {
        $this->mergeFillable([
            'comments_enabled',
        ]);

        $this->mergeCasts([
            'comments_enabled' => 'boolean',
        ]);

        $this->setAppends(array_merge($this->getAppends(), [
            'comments_count',
        ]));
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function approvedComments(): MorphMany
    {
        return $this->comments()->approved();
    }

    public function topLevelComments(): MorphMany
    {
        return $this->comments()->topLevel();
    }

    public function commentsEnabled(): bool
    {
        return $this->comments_enabled ?? true;
    }

    public function commentsCount(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->approvedComments()->count() ?? 0,
        )->shouldCache();
    }
}
