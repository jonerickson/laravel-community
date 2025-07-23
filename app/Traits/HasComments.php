<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasComments
{
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

    protected function commentsCount(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->comments()->approved()->count()
        );
    }
}
