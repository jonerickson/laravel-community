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

        if (blank($user)) {
            return;
        }

        $query->whereDoesntHaveRelation('reads', function (Builder $query) use ($user): void {
            $query->whereBelongsTo($user, 'author');
        });
    }

    public function scopeRead(Builder $query, ?User $user = null): void
    {
        $user ??= Auth::user();

        if (blank($user)) {
            return;
        }

        $query->whereRelation('reads', function (Builder $query) use ($user): void {
            $query->whereBelongsTo($user, 'author');
        });
    }

    public function userRead(?User $user = null): ?Read
    {
        $user ??= Auth::user();

        if (! $user) {
            return null;
        }

        return $this->reads->firstWhere('created_by', $user->id);
    }

    public function isReadBy(?User $user = null): bool
    {
        return $this->userRead($user) !== null;
    }

    public function markAsRead(?User $user = null): Read|bool
    {
        $user ??= Auth::user();

        if (! $user) {
            return false;
        }

        $existingRead = $this->userRead($user);
        if ($existingRead) {
            $existingRead->touch();

            return $existingRead;
        }

        return $this->reads()->updateOrCreate([]);
    }

    public function markAsUnread(?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user) {
            return false;
        }

        $existingRead = $this->userRead($user);
        if ($existingRead) {
            $existingRead->delete();

            return true;
        }

        return false;
    }

    public function hasUnreadUpdates(?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user) {
            return false;
        }

        $userRead = $this->userRead($user);

        if (! $userRead) {
            return true;
        }

        return $this->updated_at > $userRead->updated_at;
    }

    public function isReadByUser(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                $user = Auth::user();

                if (! $user) {
                    return false;
                }

                return $this->isReadBy($user);
            }
        )->shouldCache();
    }

    public function readsCount(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->reads->count(),
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
                'referenceId' => $read->author->reference_id,
                'name' => $read->author->name,
                'avatarUrl' => $read->author->avatar_url,
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
