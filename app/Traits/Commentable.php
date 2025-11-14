<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait Commentable
{
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')
            ->latest();
    }

    public function latestComment(): MorphOne
    {
        return $this->morphOne(Comment::class, 'commentable')
            ->ofMany();
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

    protected static function bootCommentable(): void
    {
        static::deleting(function ($model): void {
            /** @var static $model */
            $model->comments()->delete();
        });
    }

    protected function initializeCommentable(): void
    {
        $this->mergeFillable([
            'comments_enabled',
        ]);

        $this->mergeCasts([
            'comments_enabled' => 'boolean',
        ]);
    }
}
