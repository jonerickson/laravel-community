<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Read;
use App\Models\User;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin Eloquent
 */
trait Readable
{
    public function reads(): MorphMany
    {
        return $this->morphMany(Read::class, 'readable');
    }

    public function scopeUnread(Builder $query, ?User $user = null): void
    {
        $user ??= Auth::user();

        $query->whereDoesntHaveRelation('reads', function (Builder $query) use ($user) {
            $query->whereBelongsTo($user, 'author');
        });
    }

    public function scopeRead(Builder $query, ?User $user = null): void
    {
        $user ??= Auth::user();

        $query->whereRelation('reads', function (Builder $query) use ($user) {
            $query->whereBelongsTo($user, 'author');
        });
    }

    public function userRead(?int $userId = null): ?Read
    {
        $userId ??= Auth::id();

        if (! $userId) {
            return null;
        }

        return $this->reads()->whereCreatedBy($userId)->first();
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

        return $this->reads()->updateOrCreate([]);
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
        )->shouldCache();
    }

    public function readsCount(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->reads()->count(),
        )->shouldCache();
    }

    public function getRecentViewers(int $hours = 24, int $limit = 10): array
    {
        $recentViewers = $this->reads()
            ->where('updated_at', '>=', now()->subHours($hours))
            ->with(['author'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->unique('created_by')
            ->take($limit)
            ->values();

        return $recentViewers->map(fn (Read $read): array => [
            'user' => [
                'id' => $read->author->id,
                'name' => $read->author->name,
                'avatar' => $read->author->avatar,
            ],
            'viewed_at' => $read->updated_at->toISOString(),
        ])->toArray();
    }

    protected function initializeReadable(): void
    {
        $this->mergeAppends([
            'is_read_by_user',
            'reads_count',
        ]);
    }
}
