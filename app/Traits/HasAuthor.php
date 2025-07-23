<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasAuthor
{
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function creator(): BelongsTo
    {
        return $this->author();
    }

    public function isAuthoredBy(User $user): bool
    {
        return $this->created_by === $user->id;
    }

    protected function authorName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->author?->name ?? 'Unknown'
        );
    }
}