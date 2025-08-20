<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Read;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

trait HasReads
{
    public function reads(): MorphMany
    {
        return $this->morphMany(Read::class, 'readable');
    }

    public function userRead(?int $userId = null): ?Read
    {
        $userId ??= Auth::id();

        if (! $userId) {
            return null;
        }

        return $this->reads()->where('created_by', $userId)->first();
    }

    public function isReadBy(?int $userId = null): bool
    {
        return $this->userRead($userId) !== null;
    }

    public function markAsRead(?int $userId = null): Read|bool
    {
        $userId ??= Auth::id();

        if (! $userId) {
            return false;
        }

        $existingRead = $this->userRead($userId);
        if ($existingRead) {
            $existingRead->touch();

            return $existingRead;
        }

        return $this->reads()->create([
            'created_by' => $userId,
        ]);
    }

    public function markAsUnread(?int $userId = null): bool
    {
        $userId ??= Auth::id();

        if (! $userId) {
            return false;
        }

        $existingRead = $this->userRead($userId);
        if ($existingRead) {
            $existingRead->delete();

            return true;
        }

        return false;
    }

    public function hasUnreadUpdates(?int $userId = null): bool
    {
        $userId ??= Auth::id();

        if (! $userId) {
            return false;
        }

        $userRead = $this->userRead($userId);

        if (! $userRead) {
            return true;
        }

        return $this->updated_at > $userRead->updated_at;
    }

    public function isReadByUser(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                $userId = Auth::id();

                if (! $userId) {
                    return false;
                }

                return $this->isReadBy($userId);
            }
        );
    }

    public function readsCount(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->reads()->count(),
        )->shouldCache();
    }

    protected function initializeHasReads(): void
    {
        $this->mergeAppends([
            'is_read_by_user',
            'reads_count',
        ]);
    }
}
