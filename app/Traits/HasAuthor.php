<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait HasAuthor
{
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')
            ->withDefault([
                'id' => 0,
                'name' => 'Guest',
                'email' => config('app.email'),
            ]);
    }

    public function creator(): BelongsTo
    {
        return $this->author();
    }

    public function isAuthoredBy(User $user): bool
    {
        return $this->created_by === $user->id;
    }

    public function authorName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->author?->name ?? 'Guest'
        );
    }

    protected static function bootHasAuthor(): void
    {
        static::creating(function (Model $model): void {
            if (blank($model->getAttribute('created_by'))) {
                $model->forceFill([
                    'created_by' => Auth::id(),
                ]);
            }
        });
    }

    protected function initializeHasAuthor(): void
    {
        $this->mergeFillable([
            'created_by',
        ]);
    }
}
